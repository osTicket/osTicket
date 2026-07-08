export interface SeamManifest {
  ticketId: string;
  entryPoint: string;
  coreLogic: string;
  consumers: string[];
  inputShape: string;
  outputShape: string;
  sideEffects: string[];
  constraints: string[];
}

export interface Fixture {
  name: string;
  graceHours: number;
  start: string;
  scheduleId: number;
  expected: string | null;
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
