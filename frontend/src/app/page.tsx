import { redirect } from 'next/navigation'

const DEFAULT_LOCALE = 'ru'

export default function RootPage() {
  redirect(`/${DEFAULT_LOCALE}`)
}

