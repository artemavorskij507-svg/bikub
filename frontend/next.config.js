/** @type {import('next').NextConfig} */
const nextConfig = {
  i18n: {
    locales: ['ru', 'no', 'en'],
    defaultLocale: 'ru',
  },
  images: {
    domains: ['localhost', 'glfbikube.local'],
  },
  env: {
    NEXT_PUBLIC_API_URL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:2222/api/v1',
    NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY: process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY,
  },
}

module.exports = nextConfig
