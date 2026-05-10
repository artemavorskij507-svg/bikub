'use client'

import { useEffect, useMemo, useRef, useState } from 'react'
import { useLocale, useTranslations } from 'next-intl'
import clsx from 'clsx'
import styles from './FastOrderForm.module.css'

// Declare global google type for Google Maps API
declare global {
  // eslint-disable-next-line no-var
  var google: {
    maps: {
      places: {
        Autocomplete: new (input: HTMLInputElement, options?: unknown) => {
          addListener: (event: string, callback: () => void) => void
          getPlace: () => {
            formatted_address?: string
            name?: string
          } & Record<string, unknown>
        }
      }
      event: {
        clearInstanceListeners: (instance: unknown) => void
      }
    }
  }
}

type FastOrderFormProps = {
  onSubmit?: (payload: { service: string; address: string }) => void
}

type GoogleAutocomplete = InstanceType<typeof google.maps.places.Autocomplete>

const SERVICES = [
  { value: 'delivery', label: 'Delivery' },
  { value: 'bulky', label: 'Bulky delivery' },
  { value: 'food', label: 'Food delivery' },
  { value: 'handyman', label: 'Handyman' },
  { value: 'eco', label: 'Eco services' },
  { value: 'roadside', label: 'Roadside assistance' },
  { value: 'personal', label: 'Personal errands' }
]

function loadGooglePlaces(key?: string) {
  if (!key) return Promise.reject(new Error('Missing Google Places key'))
  if (typeof window === 'undefined') return Promise.resolve()
  if (window.google?.maps?.places) return Promise.resolve()

  return new Promise<void>((resolve, reject) => {
    const existingScript = document.querySelector<HTMLScriptElement>('script[data-google-places]')
    if (existingScript) {
      existingScript.addEventListener('load', () => resolve())
      existingScript.addEventListener('error', () => reject(new Error('Failed to load Google Places script')))
      return
    }

    const script = document.createElement('script')
    script.src = `https://maps.googleapis.com/maps/api/js?key=${key}&libraries=places`
    script.async = true
    script.defer = true
    script.dataset.googlePlaces = 'true'
    script.addEventListener('load', () => resolve())
    script.addEventListener('error', () => reject(new Error('Failed to load Google Places script')))
    document.body.appendChild(script)
  })
}

export function FastOrderForm({ onSubmit }: FastOrderFormProps) {
  const t = useTranslations('Header')
  const locale = useLocale()
  const [service, setService] = useState('delivery')
  const [address, setAddress] = useState('')
  const [expanded, setExpanded] = useState(false)
  const addressInputRef = useRef<HTMLInputElement | null>(null)
  const autocompleteRef = useRef<GoogleAutocomplete | null>(null)

  const translate = useMemo(() => (
    (key: string, fallback: string) => {
      try {
        return t(key)
      } catch {
        return fallback
      }
    }
  ), [t])

  const services = useMemo(() => {
    return SERVICES.map((item) => ({
      ...item,
      label: translate(`services.${item.value}.title`, item.label)
    }))
  }, [translate])

  useEffect(() => {
    const key = process.env.NEXT_PUBLIC_GOOGLE_PLACES_API_KEY
    loadGooglePlaces(key)
      .then(() => {
        if (!addressInputRef.current || !window.google?.maps?.places) return
        const autocomplete = new window.google.maps.places.Autocomplete(addressInputRef.current, {
          fields: ['formatted_address', 'geometry', 'name']
        })
        autocomplete.addListener('place_changed', () => {
          const place = autocomplete.getPlace()
          if (place.formatted_address) {
            setAddress(place.formatted_address)
          } else if (place.name) {
            setAddress(place.name)
          }
        })
        autocompleteRef.current = autocomplete
      })
      .catch(() => {
        // gracefully degrade – we keep manual input
      })

    return () => {
      if (autocompleteRef.current) {
        google.maps.event.clearInstanceListeners(autocompleteRef.current)
        autocompleteRef.current = null
      }
    }
  }, [])

  function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!address.trim()) {
      setExpanded(true)
      addressInputRef.current?.focus()
      return
    }

    const payload = { service, address }
    onSubmit?.(payload)

    // Ideally navigate to booking page with prefilled query params
    const url = `/${locale}/order?service=${encodeURIComponent(service)}&address=${encodeURIComponent(address)}`
    window.location.assign(url)
  }

  return (
    <div className={clsx(styles.wrapper, expanded && styles.wrapperExpanded)}>
      <form className={styles.form} onSubmit={handleSubmit}>
        <div className={styles.inputGroup}>
          <span className={styles.inputIcon} aria-hidden="true">🛠️</span>
          <input
            className={styles.input}
            value={services.find((item) => item.value === service)?.label ?? translate(`services.${service}.title`, service)}
            onFocus={() => setExpanded(true)}
            readOnly
            aria-label={translate('fastOrder.serviceLabel', 'Select service')}
          />
        </div>
        <div className={styles.inputGroup}>
          <span className={styles.inputIcon} aria-hidden="true">📍</span>
          <input
            ref={addressInputRef}
            className={styles.input}
            value={address}
            placeholder={translate('fastOrder.addressPlaceholder', 'Where should we arrive?')}
            onChange={(event) => setAddress(event.target.value)}
            onFocus={() => setExpanded(true)}
            aria-label={translate('fastOrder.addressLabel', 'Pickup address')}
            autoComplete="off"
          />
        </div>
        <button type="submit" className={styles.button}>
          <span>{translate('fastOrder.cta', 'Quick order')}</span>
          <span aria-hidden="true">↗</span>
        </button>
      </form>

      {expanded && (
        <div className={styles.expandedOptions}>
          <div className={styles.optionRow}>
            <span className={styles.inlineLabel}>{translate('fastOrder.chooseService', 'Service')}</span>
            <select
              className={styles.select}
              value={service}
              onChange={(event) => setService(event.target.value)}
            >
              {services.map((item) => (
                <option key={item.value} value={item.value}>
                  {item.label}
                </option>
              ))}
            </select>
          </div>
          <div className={styles.optionRow}>
            <span className={styles.inlineLabel}>{translate('fastOrder.destination', 'Destination')}</span>
            <input
              className={styles.select}
              value={address}
              placeholder={translate('fastOrder.addressPlaceholder', 'Where should we arrive?')}
              onChange={(event) => setAddress(event.target.value)}
            />
          </div>
        </div>
      )}
    </div>
  )
}

declare global {
  interface Window {
    google: typeof google
  }
}
