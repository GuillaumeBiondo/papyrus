const MAX_ERRORS = 20
const MAX_CALLS  = 10

export interface ApiCall {
  method:   string
  url:      string
  status:   number | null
  request:  string
  response: string
  at:       string
}

const consoleErrors: string[] = []
const apiCalls: ApiCall[]     = []

export function installConsoleInterceptor() {
  const orig = { error: console.error, warn: console.warn }

  console.error = (...args: unknown[]) => {
    push(consoleErrors, '[error] ' + args.map(stringify).join(' '), MAX_ERRORS)
    orig.error.apply(console, args)
  }
  console.warn = (...args: unknown[]) => {
    push(consoleErrors, '[warn] ' + args.map(stringify).join(' '), MAX_ERRORS)
    orig.warn.apply(console, args)
  }
}

export function recordApiCall(call: ApiCall) {
  push(apiCalls, call, MAX_CALLS)
}

export function getBugSnapshot() {
  return {
    consoleErrors: [...consoleErrors],
    apiCalls:      [...apiCalls],
  }
}

function push<T>(arr: T[], item: T, max: number) {
  arr.push(item)
  if (arr.length > max) arr.shift()
}

function stringify(v: unknown): string {
  if (typeof v === 'string') return v
  try { return JSON.stringify(v) } catch { return String(v) }
}
