import { Client } from "@modelcontextprotocol/sdk/client/index.js";
import { StreamableHTTPClientTransport } from "@modelcontextprotocol/sdk/client/streamableHttp.js";

const LINEAR_MCP_URL = "https://mcp.linear.app/mcp";
const SLA_MODERNIZATION_PROJECT = "SLA Modernization";
const READY_STATUS = "Ready";

/** Modernize (MOD) team workflow states — verified via list_issue_statuses */
export const STATUS_IN_PROGRESS = "In Progress";
export const STATUS_IN_REVIEW = "In Review";

export interface LinearTicket {
  title: string;
  description: string;
}

interface GetIssueResult {
  title: string;
  description?: string | null;
}

interface ListIssuesResult {
  issues: Array<{ id: string }>;
}

type ToolCallResult = {
  content?: Array<{ type: string; text?: string }>;
  structuredContent?: unknown;
  isError?: boolean;
};

function linearAuthHeader(): string {
  const apiKey = process.env.LINEAR_API_KEY;
  if (!apiKey) {
    throw new Error(
      "LINEAR_API_KEY is required to call Linear MCP tools (Settings → Security & access in Linear)"
    );
  }
  return `Bearer ${apiKey}`;
}

async function withLinearClient<T>(fn: (client: Client) => Promise<T>): Promise<T> {
  const transport = new StreamableHTTPClientTransport(new URL(LINEAR_MCP_URL), {
    requestInit: {
      headers: {
        Authorization: linearAuthHeader(),
      },
    },
  });

  const client = new Client({ name: "osticket-strangler-orchestrator", version: "1.0.0" });
  await client.connect(transport);
  try {
    return await fn(client);
  } finally {
    await transport.close();
  }
}

function parseToolResult<T>(result: ToolCallResult): T {
  if (result.isError) {
    const message =
      result.content?.find((block) => block.type === "text")?.text ??
      "Linear MCP tool call failed";
    throw new Error(message);
  }

  if (result.structuredContent !== undefined) {
    return result.structuredContent as T;
  }

  const text = result.content?.find((block) => block.type === "text")?.text;
  if (!text) {
    throw new Error("Linear MCP tool returned no content");
  }

  return JSON.parse(text) as T;
}

async function callLinearTool<T>(name: string, arguments_: Record<string, unknown>): Promise<T> {
  return withLinearClient(async (client) => {
    const result = await client.callTool({ name, arguments: arguments_ });
    return parseToolResult<T>(result);
  });
}

export async function getLinearTicket(ticketId: string): Promise<LinearTicket> {
  const issue = await callLinearTool<GetIssueResult>("get_issue", { id: ticketId });
  return {
    title: issue.title,
    description: issue.description ?? "",
  };
}

export async function findReadyTicket(): Promise<string | null> {
  const result = await callLinearTool<ListIssuesResult>("list_issues", {
    project: SLA_MODERNIZATION_PROJECT,
    state: READY_STATUS,
    limit: 1,
  });
  return result.issues[0]?.id ?? null;
}

export async function updateTicketStatus(ticketId: string, status: string): Promise<void> {
  await callLinearTool("save_issue", { id: ticketId, state: status });
}
