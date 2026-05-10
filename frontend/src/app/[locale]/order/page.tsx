'use client'

import { useCallback, useEffect, useState } from 'react'
import { useTranslations, useLocale } from 'next-intl'
import { Header } from '@/components/Header'
import { Footer } from '@/components/Footer'
import { OrderForm } from '@/components/OrderForm'
import { PaymentForm } from '@/components/PaymentForm'
import type { OrderSubmission, ServiceSummary } from '@/types/order'

export default function OrderPage() {
  const t = useTranslations('OrderPage')
  const locale = useLocale()
  const [service, setService] = useState<ServiceSummary | null>(null)
  const [loading, setLoading] = useState(true)
  const [step, setStep] = useState<'form' | 'payment' | 'confirmation'>('form')
  const [orderData, setOrderData] = useState<OrderSubmission | null>(null)

  const fetchService = useCallback(async (serviceId: string) => {
    try {
      const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/public/services/${serviceId}?locale=${locale}`)
      const data = await response.json()
      setService({
        id: data.service?.id,
        name: data.service?.name,
        description: data.service?.description,
        price: data.service?.price,
        currency: data.service?.currency
      })
    } catch (error) {
      console.error('Failed to fetch service:', error)
    } finally {
      setLoading(false)
    }
  }, [locale])

  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search)
    const serviceId = urlParams.get('service')

    if (serviceId) {
      void fetchService(serviceId)
    } else {
      setLoading(false)
    }
  }, [fetchService])

  const handleOrderSubmit = (data: OrderSubmission) => {
    setOrderData(data)
    setStep('payment')
  }

  const handlePaymentComplete = () => {
    setStep('confirmation')
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">{t('loading')}</p>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      
      <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="bg-white rounded-lg shadow-md overflow-hidden">
          <div className="px-6 py-4 bg-blue-600 text-white">
            <h1 className="text-2xl font-bold">
              {t('title')}
            </h1>
            {service && (
              <p className="text-blue-100 mt-1">
                {t('service')}: {service.name}
              </p>
            )}
          </div>

          <div className="p-6">
            {/* Progress Steps */}
            <div className="flex items-center justify-center mb-8">
              <div className="flex items-center">
                <div className={`w-8 h-8 rounded-full flex items-center justify-center ${
                  step === 'form' ? 'bg-blue-600 text-white' : 
                  step === 'payment' || step === 'confirmation' ? 'bg-green-600 text-white' : 
                  'bg-gray-300 text-gray-600'
                }`}>
                  1
                </div>
                <div className={`w-16 h-1 ${
                  step === 'payment' || step === 'confirmation' ? 'bg-green-600' : 'bg-gray-300'
                }`}></div>
                <div className={`w-8 h-8 rounded-full flex items-center justify-center ${
                  step === 'payment' ? 'bg-blue-600 text-white' : 
                  step === 'confirmation' ? 'bg-green-600 text-white' : 
                  'bg-gray-300 text-gray-600'
                }`}>
                  2
                </div>
                <div className={`w-16 h-1 ${
                  step === 'confirmation' ? 'bg-green-600' : 'bg-gray-300'
                }`}></div>
                <div className={`w-8 h-8 rounded-full flex items-center justify-center ${
                  step === 'confirmation' ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600'
                }`}>
                  3
                </div>
              </div>
            </div>

            {/* Step Content */}
            {step === 'form' && (
              <OrderForm 
                service={service}
                onSubmit={handleOrderSubmit}
              />
            )}

            {step === 'payment' && orderData && (
              <PaymentForm 
                orderData={orderData}
                service={service}
                onComplete={handlePaymentComplete}
              />
            )}

            {step === 'confirmation' && (
              <div className="text-center py-8">
                <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                  <svg className="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <h2 className="text-2xl font-bold text-gray-900 mb-2">
                  {t('orderConfirmed')}
                </h2>
                <p className="text-gray-600 mb-6">
                  {t('orderConfirmedDesc')}
                </p>
                <div className="bg-gray-50 rounded-lg p-4 mb-6">
                  <p className="text-sm text-gray-600">
                    {t('orderNumber')}: <span className="font-mono font-semibold">{orderData?.orderNumber}</span>
                  </p>
                </div>
                <button
                  onClick={() => window.location.href = `/${locale}`}
                  className="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors"
                >
                  {t('backToHome')}
                </button>
              </div>
            )}
          </div>
        </div>
      </main>

      <Footer />
    </div>
  )
}
