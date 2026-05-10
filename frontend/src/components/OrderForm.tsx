'use client'

import { useState } from 'react'
import { useTranslations } from 'next-intl'
import type { OrderFormState, OrderSubmission, ServiceSummary } from '@/types/order'

interface OrderFormProps {
  service: ServiceSummary | null
  onSubmit: (data: OrderSubmission) => void
}

type FormErrors = Partial<Record<
  | 'customerName'
  | 'customerEmail'
  | 'customerPhone'
  | 'addressStreet'
  | 'addressCity'
  | 'addressPostalCode'
  | 'scheduleSlot',
  string
>>

export function OrderForm({ service, onSubmit }: OrderFormProps) {
  const t = useTranslations('OrderForm')
  const [formData, setFormData] = useState<OrderFormState>({
    customer: {
      name: '',
      email: '',
      phone: ''
    },
    address: {
      street: '',
      city: '',
      postalCode: '',
      latitude: 0,
      longitude: 0
    },
    scheduleSlot: '',
    notes: ''
  })

  const [errors, setErrors] = useState<FormErrors>({})
  const [loading, setLoading] = useState(false)

  const handleInputChange = (field: string, value: string | number) => {
    if (field.includes('.')) {
      const [parent, child] = field.split('.')
      setFormData((prev) => {
        const parentValue = prev[parent as keyof typeof prev]
        return {
          ...prev,
          [parent]: {
            ...(typeof parentValue === 'object' && parentValue !== null ? parentValue : {}),
            [child]: value
          }
        }
      })
    } else {
      setFormData((prev) => ({
        ...prev,
        [field]: value
      }))
    }
  }

  const validateForm = () => {
    const newErrors: FormErrors = {}

    if (!formData.customer.name) newErrors.customerName = t('errors.nameRequired')
    if (!formData.customer.email) newErrors.customerEmail = t('errors.emailRequired')
    if (!formData.customer.phone) newErrors.customerPhone = t('errors.phoneRequired')
    if (!formData.address.street) newErrors.addressStreet = t('errors.streetRequired')
    if (!formData.address.city) newErrors.addressCity = t('errors.cityRequired')
    if (!formData.address.postalCode) newErrors.addressPostalCode = t('errors.postalCodeRequired')
    if (!formData.scheduleSlot) newErrors.scheduleSlot = t('errors.slotRequired')

    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    
    if (!validateForm()) return

    setLoading(true)
    
    try {
      // Geocode address (simplified)
      const geocodeResponse = await fetch(
        `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(
          `${formData.address.street}, ${formData.address.city}, ${formData.address.postalCode}`
        )}.json?access_token=${process.env.NEXT_PUBLIC_MAPBOX_TOKEN || 'pk.test'}`
      )
      
      const geocodeData = await geocodeResponse.json()
      
      if (geocodeData.features && geocodeData.features.length > 0) {
        const [longitude, latitude] = geocodeData.features[0].center
        setFormData((prev) => ({
          ...prev,
          address: {
            ...prev.address,
            latitude,
            longitude
          }
        }))
      }

      const submission: OrderSubmission = {
        ...formData,
        address: {
          ...formData.address,
          latitude: geocodeData.features?.[0]?.center?.[1] ?? formData.address.latitude,
          longitude: geocodeData.features?.[0]?.center?.[0] ?? formData.address.longitude
        },
        serviceId: service?.id,
        totalAmount: service?.price ?? 0,
        currency: service?.currency ?? 'NOK'
      }

      onSubmit(submission)
    } catch (error) {
      console.error('Geocoding failed:', error)
      // Continue without geocoding
      onSubmit({
        ...formData,
        serviceId: service?.id,
        totalAmount: service?.price ?? 0,
        currency: service?.currency ?? 'NOK'
      })
    } finally {
      setLoading(false)
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      {/* Customer Information */}
      <div className="bg-gray-50 rounded-lg p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">
          {t('customerInfo')}
        </h3>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t('fullName')} *
            </label>
            <input
              type="text"
              value={formData.customer.name}
              onChange={(e) => handleInputChange('customer.name', e.target.value)}
              className={`w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.customerName ? 'border-red-500' : 'border-gray-300'
              }`}
              placeholder={t('placeholders.fullName')}
            />
            {errors.customerName && (
              <p className="text-red-500 text-sm mt-1">{errors.customerName}</p>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t('email')} *
            </label>
            <input
              type="email"
              value={formData.customer.email}
              onChange={(e) => handleInputChange('customer.email', e.target.value)}
              className={`w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.customerEmail ? 'border-red-500' : 'border-gray-300'
              }`}
              placeholder={t('placeholders.email')}
            />
            {errors.customerEmail && (
              <p className="text-red-500 text-sm mt-1">{errors.customerEmail}</p>
            )}
          </div>

          <div className="md:col-span-2">
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t('phone')} *
            </label>
            <input
              type="tel"
              value={formData.customer.phone}
              onChange={(e) => handleInputChange('customer.phone', e.target.value)}
              className={`w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.customerPhone ? 'border-red-500' : 'border-gray-300'
              }`}
              placeholder={t('placeholders.phone')}
            />
            {errors.customerPhone && (
              <p className="text-red-500 text-sm mt-1">{errors.customerPhone}</p>
            )}
          </div>
        </div>
      </div>

      {/* Address Information */}
      <div className="bg-gray-50 rounded-lg p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">
          {t('addressInfo')}
        </h3>
        
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t('streetAddress')} *
            </label>
            <input
              type="text"
              value={formData.address.street}
              onChange={(e) => handleInputChange('address.street', e.target.value)}
              className={`w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.addressStreet ? 'border-red-500' : 'border-gray-300'
              }`}
              placeholder={t('placeholders.streetAddress')}
            />
            {errors.addressStreet && (
              <p className="text-red-500 text-sm mt-1">{errors.addressStreet}</p>
            )}
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('city')} *
              </label>
              <input
                type="text"
                value={formData.address.city}
                onChange={(e) => handleInputChange('address.city', e.target.value)}
                className={`w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                  errors.addressCity ? 'border-red-500' : 'border-gray-300'
                }`}
                placeholder={t('placeholders.city')}
              />
              {errors.addressCity && (
                <p className="text-red-500 text-sm mt-1">{errors.addressCity}</p>
              )}
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('postalCode')} *
              </label>
              <input
                type="text"
                value={formData.address.postalCode}
                onChange={(e) => handleInputChange('address.postalCode', e.target.value)}
                className={`w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                  errors.addressPostalCode ? 'border-red-500' : 'border-gray-300'
                }`}
                placeholder={t('placeholders.postalCode')}
              />
              {errors.addressPostalCode && (
                <p className="text-red-500 text-sm mt-1">{errors.addressPostalCode}</p>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Schedule Slot */}
      <div className="bg-gray-50 rounded-lg p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">
          {t('scheduleSlot')}
        </h3>
        
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            {t('preferredTime')} *
          </label>
          <select
            value={formData.scheduleSlot}
            onChange={(e) => handleInputChange('scheduleSlot', e.target.value)}
            className={`w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 ${
              errors.scheduleSlot ? 'border-red-500' : 'border-gray-300'
            }`}
          >
            <option value="">{t('selectTimeSlot')}</option>
            <option value="morning">{t('timeSlots.morning')}</option>
            <option value="afternoon">{t('timeSlots.afternoon')}</option>
            <option value="evening">{t('timeSlots.evening')}</option>
          </select>
          {errors.scheduleSlot && (
            <p className="text-red-500 text-sm mt-1">{errors.scheduleSlot}</p>
          )}
        </div>
      </div>

      {/* Additional Notes */}
      <div className="bg-gray-50 rounded-lg p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">
          {t('additionalInfo')}
        </h3>
        
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            {t('notes')}
          </label>
          <textarea
            value={formData.notes}
            onChange={(e) => handleInputChange('notes', e.target.value)}
            rows={4}
            className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder={t('placeholders.notes')}
          />
        </div>
      </div>

      {/* Service Summary */}
      {service && (
        <div className="bg-blue-50 rounded-lg p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">
            {t('serviceSummary')}
          </h3>
          
          <div className="flex justify-between items-center">
            <div>
              <h4 className="font-semibold text-gray-900">{service.name}</h4>
              <p className="text-gray-600 text-sm">{service.description}</p>
            </div>
            <div className="text-right">
              <p className="text-2xl font-bold text-blue-600">
                {service.price} {service.currency}
              </p>
            </div>
          </div>
        </div>
      )}

      {/* Submit Button */}
      <div className="flex justify-end">
        <button
          type="submit"
          disabled={loading}
          className="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {loading ? t('processing') : t('continueToPayment')}
        </button>
      </div>
    </form>
  )
}
