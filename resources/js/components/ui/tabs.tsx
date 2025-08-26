import * as React from "react"

import { cn } from "@/lib/utils"

type TabsContextValue = {
  value: string
  setValue: (v: string) => void
}

const TabsContext = React.createContext<TabsContextValue | null>(null)

export function Tabs({ defaultValue, value: valueProp, onValueChange, className, children }: {
  defaultValue?: string
  value?: string
  onValueChange?: (v: string) => void
  className?: string
  children?: React.ReactNode
}) {
  const [internal, setInternal] = React.useState(defaultValue || "")
  const controlled = typeof valueProp === "string"
  const value = controlled ? (valueProp as string) : internal
  const setValue = (v: string) => {
    if (!controlled) setInternal(v)
    onValueChange?.(v)
  }
  return (
    <TabsContext.Provider value={{ value, setValue }}>
      <div className={cn("flex flex-col gap-4", className)}>{children}</div>
    </TabsContext.Provider>
  )
}

export function TabsList({ className, children }: React.ComponentProps<"div">) {
  return (
    <div className={cn("inline-flex items-center gap-2 rounded-lg bg-muted p-1", className)}>{children}</div>
  )
}

export function TabsTrigger({ value, className, children }: { value: string } & React.ComponentProps<"button">) {
  const ctx = React.useContext(TabsContext)
  if (!ctx) return null
  const active = ctx.value === value
  return (
    <button
      onClick={() => ctx.setValue(value)}
      className={cn(
        "rounded-md px-3 py-1.5 text-sm transition-colors",
        active ? "bg-card shadow text-foreground" : "text-muted-foreground hover:text-foreground",
        className
      )}
    >
      {children}
    </button>
  )
}

export function TabsContent({ value, className, children }: { value: string } & React.ComponentProps<"div">) {
  const ctx = React.useContext(TabsContext)
  if (!ctx || ctx.value !== value) return null
  return <div className={className}>{children}</div>
}

export default Tabs


