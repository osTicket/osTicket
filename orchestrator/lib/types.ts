export interface SeamManifest {
  ticketId: string;
  entryPoint: string;
  coreLogic: string;
  consumers: string[];
  inputShape: string;
  outputShape: string;
  sideEffects: string[];
  constraints: string[];
  /** PHP file containing the facade method to patch in the strangler stage */
  facadeFile?: string;
  /** New extracted service class path under include/Services/ */
  extractionTarget?: string;
  /** PHP harness script for parity baseline capture and verification */
  harnessScript?: string;
  /** Description of fixture input fields the harness expects */
  harnessInputShape?: string;
}

export interface Fixture {
  name: string;
  branch?: string;
  /** Harness payload when not using legacy SLA fields */
  input?: Record<string, unknown>;
  /** @deprecated Legacy SLA fixtures — prefer `input` for new tickets */
  graceHours?: number;
  start?: string;
  scheduleId?: number;
  expected?: string | null;
}

export interface ParityReport {
  totalCases: number;
  passed: number;
  failed: number;
  mismatches: { name: string; expected: string | null; actual: string }[];
  gatePassed: boolean;
}

export interface PipelineState {
  ticketId: string;
  manifest?: SeamManifest;
  parityReport?: ParityReport;
  prUrl?: string;
}
