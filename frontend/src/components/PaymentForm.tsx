'use client'

import { useState, useEffect } from 'react'
import { useTranslations } from 'next-intl'
import { loadStripe } from '@stripe/stripe-js'
import type { Stripe } from '@stripe/stripe-js'
import type { OrderSubmission, ServiceSummary } from '@/types/order'

interface PaymentFormProps {
  orderData: OrderSubmission
  service: ServiceSummary | null
  onComplete: () => void
}

export function PaymentForm({ orderData, service, onComplete }: PaymentFormProps) {
  const t = useTranslations('PaymentForm')
  const [paymentMethod, setPaymentMethod] = useState<'stripe' | 'vipps'>('stripe')
  const [loading, setLoading] = useState(false)
  const [stripe, setStripe] = useState<Stripe | null>(null)

  useEffect(() => {
    const initializeStripe = async () => {
      const stripeInstance = await loadStripe(process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY!)
      setStripe(stripeInstance)
    }
    
    if (process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY) {
      initializeStripe()
    }
  }, [])

  const handleStripePayment = async () => {
    if (!stripe) return

    setLoading(true)

    try {
      // Create payment intent on backend
      const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/public/orders`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          ...orderData,
          paymentProvider: 'stripe'
        })
      })

      const responseData: { clientSecret?: string; orderId?: string } = await response.json()

      // Confirm payment with Stripe
      if (responseData.clientSecret) {
        const { error } = await stripe.confirmPayment({
          clientSecret: responseData.clientSecret,
          confirmParams: {
            return_url: `${window.location.origin}/order/success?orderId=${responseData.orderId ?? ''}`
          }
        })

        if (error) {
          console.error('Payment failed:', error)
          alert(t('paymentFailed'))
          setLoading(false)
          return
        }
      }

      onComplete()
    } catch (error) {
      console.error('Payment error:', error)
      alert(t('paymentError'))
    } finally {
      setLoading(false)
    }
  }

  const handleVippsPayment = async () => {
    setLoading(true)

    try {
      const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/public/orders`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          ...orderData,
          paymentProvider: 'vipps'
        })
      })

      const { redirectUrl } = (await response.json()) as { redirectUrl?: string }

      if (redirectUrl) {
        // Redirect to Vipps payment page
        window.location.href = redirectUrl
      } else {
        throw new Error('No redirect URL received')
      }
    } catch (error) {
      console.error('Vipps payment error:', error)
      alert(t('paymentError'))
      setLoading(false)
    }
  }

  const handlePayment = () => {
    if (paymentMethod === 'stripe') {
      handleStripePayment()
    } else {
      handleVippsPayment()
    }
  }

  return (
    <div className="space-y-6">
      {/* Order Summary */}
      <div className="bg-gray-50 rounded-lg p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">
          {t('orderSummary')}
        </h3>
        
        <div className="space-y-3">
          <div className="flex justify-between">
            <span className="text-gray-600">{t('service')}</span>
            <span className="font-semibold">{service?.name}</span>
          </div>
          
          <div className="flex justify-between">
            <span className="text-gray-600">{t('customer')}</span>
            <span>{orderData.customer.name}</span>
          </div>
          
          <div className="flex justify-between">
            <span className="text-gray-600">{t('address')}</span>
            <span className="text-right max-w-xs">
              {orderData.address.street}, {orderData.address.city}
            </span>
          </div>
          
          <div className="flex justify-between">
            <span className="text-gray-600">{t('timeSlot')}</span>
            <span>{t(`timeSlots.${orderData.scheduleSlot}`)}</span>
          </div>
          
          <div className="border-t pt-3">
            <div className="flex justify-between text-lg font-bold">
              <span>{t('total')}</span>
              <span className="text-blue-600">
                {orderData.totalAmount} {orderData.currency}
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Payment Method Selection */}
      <div className="bg-gray-50 rounded-lg p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">
          {t('paymentMethod')}
        </h3>
        
        <div className="space-y-4">
          {/* Stripe Payment */}
          <div className="flex items-center">
            <input
              type="radio"
              id="stripe"
              name="paymentMethod"
              value="stripe"
              checked={paymentMethod === 'stripe'}
              onChange={(e) => setPaymentMethod(e.target.value as 'stripe')}
              className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
            />
            <label htmlFor="stripe" className="ml-3 flex items-center">
              <div className="w-8 h-8 bg-blue-600 rounded flex items-center justify-center mr-3">
                <span className="text-white text-xs font-bold">S</span>
              </div>
              <div>
                <div className="text-sm font-medium text-gray-900">
                  {t('stripe.title')}
                </div>
                <div className="text-sm text-gray-500">
                  {t('stripe.description')}
                </div>
              </div>
            </label>
          </div>

          {/* Vipps Payment */}
          <div className="flex items-center">
            <input
              type="radio"
              id="vipps"
              name="paymentMethod"
              value="vipps"
              checked={paymentMethod === 'vipps'}
              onChange={(e) => setPaymentMethod(e.target.value as 'vipps')}
              className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
            />
            <label htmlFor="vipps" className="ml-3 flex items-center">
              <div className="w-8 h-8 bg-purple-600 rounded flex items-center justify-center mr-3">
                <span className="text-white text-xs font-bold">V</span>
              </div>
              <div>
                <div className="text-sm font-medium text-gray-900">
                  {t('vipps.title')}
                </div>
                <div className="text-sm text-gray-500">
                  {t('vipps.description')}
                </div>
              </div>
            </label>
          </div>
        </div>
      </div>

      {/* Security Notice */}
      <div className="bg-green-50 border border-green-200 rounded-lg p-4">
        <div className="flex">
          <div className="flex-shrink-0">
            <svg className="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
              <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
            </svg>
          </div>
          <div className="ml-3">
            <h3 className="text-sm font-medium text-green-800">
              {t('security.title')}
            </h3>
            <div className="mt-2 text-sm text-green-700">
              <p>{t('security.description')}</p>
            </div>
          </div>
        </div>
      </div>

      {/* Payment Button */}
      <div className="flex justify-end space-x-4">
        <button
          onClick={() => window.history.back()}
          className="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
        >
          {t('back')}
        </button>
        
        <button
          onClick={handlePayment}
          disabled={loading}
          className="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {loading ? (
            <div className="flex items-center">
              <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
              {t('processing')}
            </div>
          ) : (
            t('payNow')
          )}
        </button>
      </div>
    </div>
  )
}
