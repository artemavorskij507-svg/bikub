import '../globals.css'
import { Inter } from 'next/font/google'
import { NextIntlClientProvider } from 'next-intl'
import { getMessages } from 'next-intl/server'
import { notFound } from 'next/navigation'

const inter = Inter({ subsets: ['latin'] })

const locales = ['ru', 'no', 'en']

export function generateStaticParams() {
  return locales.map((locale) => ({ locale }))
}

export default async function RootLayout({
  children,
  params: { locale }
}: {
  children: React.ReactNode
  params: { locale: string }
}) {
  if (!locales.includes(locale)) notFound()

  const messages = await getMessages()

  return (
    <html lang={locale}>
      <head>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="description" content="GLF BiKube - Professional bike care services in Norway" />
        <meta property="og:title" content="GLF BiKube - Bike Care Services" />
        <meta property="og:description" content="Professional bike maintenance, repair, and care services" />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://glfbikube.no" />
        <meta property="og:image" content="/og-image.jpg" />
        <link rel="icon" href="/favicon.ico" />
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{
            __html: JSON.stringify({
              "@context": "https://schema.org",
              "@type": "Organization",
              "name": "GLF BiKube",
              "url": "https://glfbikube.no",
              "logo": "https://glfbikube.no/logo.png",
              "description": "Professional bike care services",
              "address": {
                "@type": "PostalAddress",
                "addressCountry": "NO"
              },
              "contactPoint": {
                "@type": "ContactPoint",
                "telephone": "+47-XXX-XXXX",
                "contactType": "customer service"
              }
            })
          }}
        />
      </head>
      <body className={inter.className}>
        <NextIntlClientProvider messages={messages}>
          {children}
        </NextIntlClientProvider>
      </body>
    </html>
  )
}
