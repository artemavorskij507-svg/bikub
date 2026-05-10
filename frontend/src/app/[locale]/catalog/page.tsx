import { useTranslations } from 'next-intl'
import { Header } from '@/components/Header'
import { Footer } from '@/components/Footer'
import { CatalogFilters } from '@/components/CatalogFilters'
import { ServiceGrid } from '@/components/ServiceGrid'

export default function CatalogPage() {
  const t = useTranslations('CatalogPage')

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      
      <main>
        <div className="bg-white py-12">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 className="text-3xl font-bold text-gray-900 text-center mb-8">
              {t('title')}
            </h1>
            <p className="text-lg text-gray-600 text-center max-w-2xl mx-auto">
              {t('subtitle')}
            </p>
          </div>
        </div>

        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div className="flex flex-col lg:flex-row gap-8">
            <div className="lg:w-1/4">
              <CatalogFilters />
            </div>
            <div className="lg:w-3/4">
              <ServiceGrid />
            </div>
          </div>
        </div>
      </main>

      <Footer />
    </div>
  )
}
