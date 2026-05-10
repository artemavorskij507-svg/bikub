export interface ServiceSummary {
  id?: string
  name?: string
  description?: string
  price?: number
  currency?: string
}

export interface CustomerInfo {
  name: string
  email: string
  phone: string
}

export interface AddressInfo {
  street: string
  city: string
  postalCode: string
  latitude: number
  longitude: number
}

export type ScheduleSlot = 'morning' | 'afternoon' | 'evening' | ''

export interface OrderFormState {
  customer: CustomerInfo
  address: AddressInfo
  scheduleSlot: ScheduleSlot
  notes: string
}

export interface OrderSubmission extends OrderFormState {
  serviceId?: string
  totalAmount: number
  currency: string
  orderNumber?: string
}

export type PaymentMethod = 'stripe' | 'vipps'

export interface PaymentIntentResponse {
  clientSecret?: string
  orderId?: string
}

export interface VippsPaymentResponse {
  redirectUrl?: string
}
