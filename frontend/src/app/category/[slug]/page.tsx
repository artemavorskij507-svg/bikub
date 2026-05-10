import { redirect } from 'next/navigation'

const DEFAULT_LOCALE = 'ru'

export default function CategoryRedirectPage({
  params,
}: Readonly<{
  params: { slug: string }
}>) {
  const { slug } = params

  redirect(`/${DEFAULT_LOCALE}/catalog?category=${encodeURIComponent(slug)}`)
}
