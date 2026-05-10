import { redirect } from 'next/navigation'

export default function LocaleCategoryRedirectPage({
  params,
}: Readonly<{
  params: { locale: string; slug: string }
}>) {
  const { locale, slug } = params

  redirect(`/${locale}/catalog?category=${encodeURIComponent(slug)}`)
}
