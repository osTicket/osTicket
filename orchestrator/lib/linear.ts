import type { ParityReport, SeamManifest } from "./types";
import { paritySummary, passedFixtureNames } from "./slack";

const LINEAR_GRAPHQL_URL = "https://api.linear.app/graphql";
const SLA_MODERNIZATION_PROJECT = "SLA Modernization";
const READY_STATUS = "Ready";

const RETRYABLE_STATUS = new Set([408, 429, 500, 502, 503, 520, 522, 524, 525]);
const MAX_RETRIES = 4;
const BASE_DELAY_MS = 1000;

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

/** Modernize (MOD) team workflow states — verified via list_issue_statuses */
export const STATUS_IN_PROGRESS = "In Progress";
export const STATUS_IN_REVIEW = "In Review";

export interface LinearTicket {
  title: string;
  description: string;
}

interface GraphQLResponse<T> {
  data?: T;
  errors?: Array<{ message: string }>;
}

function requireApiKey(): string {
  const apiKey = process.env.LINEAR_API_KEY;
  if (!apiKey) {
    throw new Error(
      "LINEAR_API_KEY is required (Settings → Account → Security & access in Linear)"
    );
  }
  return apiKey;
}

async function linearGraphQL<T>(
  query: string,
  variables?: Record<string, unknown>
): Promise<T> {
  let lastError: Error | undefined;

  for (let attempt = 0; attempt <= MAX_RETRIES; attempt++) {
    try {
      const response = await fetch(LINEAR_GRAPHQL_URL, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: requireApiKey(),
        },
        body: JSON.stringify({ query, variables }),
      });

      if (!response.ok) {
        const err = new Error(
          `Linear API error: ${response.status} ${response.statusText}`
        );
        if (RETRYABLE_STATUS.has(response.status) && attempt < MAX_RETRIES) {
          lastError = err;
          await sleep(BASE_DELAY_MS * 2 ** attempt);
          continue;
        }
        throw err;
      }

      const json = (await response.json()) as GraphQLResponse<T>;
      if (json.errors?.length) {
        throw new Error(json.errors.map((error) => error.message).join("; "));
      }
      if (!json.data) {
        throw new Error("Linear API returned no data");
      }

      return json.data;
    } catch (err) {
      const error = err instanceof Error ? err : new Error(String(err));
      const retryable =
        error.message.includes("fetch failed") ||
        error.message.includes("ECONNRESET") ||
        error.message.includes("ETIMEDOUT");
      if (retryable && attempt < MAX_RETRIES) {
        lastError = error;
        await sleep(BASE_DELAY_MS * 2 ** attempt);
        continue;
      }
      throw error;
    }
  }

  throw lastError ?? new Error("Linear API request failed after retries");
}

export async function getLinearTicket(ticketId: string): Promise<LinearTicket> {
  const data = await linearGraphQL<{
    issue: { title: string; description: string | null } | null;
  }>(
    `query GetIssue($id: String!) {
      issue(id: $id) {
        title
        description
      }
    }`,
    { id: ticketId }
  );

  if (!data.issue) {
    throw new Error(`Linear issue not found: ${ticketId}`);
  }

  return {
    title: data.issue.title,
    description: data.issue.description ?? "",
  };
}

export async function findReadyTicket(): Promise<string | null> {
  const data = await linearGraphQL<{
    issues: { nodes: Array<{ identifier: string }> };
  }>(
    `query FindReadyTicket($project: String!, $state: String!) {
      issues(
        filter: {
          project: { name: { eq: $project } }
          state: { name: { eq: $state } }
        }
        first: 1
      ) {
        nodes {
          identifier
        }
      }
    }`,
    { project: SLA_MODERNIZATION_PROJECT, state: READY_STATUS }
  );

  return data.issues.nodes[0]?.identifier ?? null;
}

export async function updateTicketStatus(ticketId: string, status: string): Promise<void> {
  const issueData = await linearGraphQL<{
    issue: {
      team: {
        states: { nodes: Array<{ id: string; name: string }> };
      };
    } | null;
  }>(
    `query IssueStates($id: String!) {
      issue(id: $id) {
        team {
          states {
            nodes {
              id
              name
            }
          }
        }
      }
    }`,
    { id: ticketId }
  );

  if (!issueData.issue) {
    throw new Error(`Linear issue not found: ${ticketId}`);
  }

  const state = issueData.issue.team.states.nodes.find((node) => node.name === status);
  if (!state) {
    throw new Error(`Workflow state not found for ${ticketId}: ${status}`);
  }

  const result = await linearGraphQL<{
    issueUpdate: { success: boolean };
  }>(
    `mutation UpdateIssueState($id: String!, $stateId: String!) {
      issueUpdate(id: $id, input: { stateId: $stateId }) {
        success
      }
    }`,
    { id: ticketId, stateId: state.id }
  );

  if (!result.issueUpdate.success) {
    throw new Error(`Failed to update ${ticketId} to ${status}`);
  }
}

export function buildInReviewComment(
  manifest: SeamManifest,
  report: ParityReport,
  prUrl: string
): string {
  const fixtureList = passedFixtureNames(manifest.ticketId, report)
    .map((name) => `- ${name}`)
    .join("\n");
  const prLine = prUrl
    ? `[View pull request](${prUrl})`
    : "_PR URL not returned by agent_";

  return [
    "## Pipeline complete",
    "",
    "The strangler extraction pipeline finished successfully.",
    "",
    "### Completed stages",
    "",
    `- **Cartography** — Seam mapped at \`${manifest.entryPoint}\``,
    "- **Fixture generation** — Parity inputs proposed from manifest branches",
    `- **Extraction** — \`${manifest.extractionTarget ?? "(see PR)"}\` created`,
    `- **Strangler** — \`${manifest.facadeFile ?? "(see PR)"}\` patched to delegate`,
    `- **Verification** — ${paritySummary(report)}`,
    "- **Pull request** — Opened for review",
    "",
    "### Parity verification",
    "",
    fixtureList,
    "",
    "### Pull request",
    "",
    prLine,
  ].join("\n");
}

export async function addIssueComment(ticketId: string, body: string): Promise<void> {
  const result = await linearGraphQL<{
    commentCreate: { success: boolean };
  }>(
    `mutation CreateComment($input: CommentCreateInput!) {
      commentCreate(input: $input) {
        success
      }
    }`,
    { input: { issueId: ticketId, body } }
  );

  if (!result.commentCreate.success) {
    throw new Error(`Failed to add comment on ${ticketId}`);
  }
}
