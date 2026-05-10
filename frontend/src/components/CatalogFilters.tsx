'use client'

import { useState } from 'react'
import { useTranslations } from 'next-intl'

export function CatalogFilters() {
  const t = useTranslations('CatalogFilters')
  const [filters, setFilters] = useState({
    category: '',
    zone: '',
    slot: '',
    partner: '',
    priceRange: [0, 1000]
  })

  const categories = [
    { value: 'care', label: t('categories.care') },
    { value: 'eco', label: t('categories.eco') },
    { value: 'tow', label: t('categories.tow') },
    { value: 'rent', label: t('categories.rent') },
    { value: 'shuttle', label: t('categories.shuttle') },
    { value: 'master', label: t('categories.master') },
    { value: 'food', label: t('categories.food') },
    { value: 'market', label: t('categories.market') },
  ]

  const zones = [
    { value: 'oslo-center', label: t('zones.osloCenter') },
    { value: 'oslo-west', label: t('zones.osloWest') },
    { value: 'oslo-east', label: t('zones.osloEast') },
    { value: 'bergen', label: t('zones.bergen') },
    { value: 'trondheim', label: t('zones.trondheim') },
  ]

  const timeSlots = [
    { value: 'morning', label: t('slots.morning') },
    { value: 'afternoon', label: t('slots.afternoon') },
    { value: 'evening', label: t('slots.evening') },
  ]

  const handleFilterChange = <Key extends keyof typeof filters>(key: Key, value: (typeof filters)[Key]) => {
    setFilters((prev) => ({ ...prev, [key]: value }))
  }

  return (
    <div className="bg-white rounded-lg shadow-md p-6">
      <h3 className="text-lg font-semibold text-gray-900 mb-4">
        {t('title')}
      </h3>
      
      <div className="space-y-6">
        {/* Category Filter */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            {t('category')}
          </label>
          <select
            value={filters.category}
            onChange={(e) => handleFilterChange('category', e.target.value)}
            className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">{t('allCategories')}</option>
            {categories.map(category => (
              <option key={category.value} value={category.value}>
                {category.label}
              </option>
            ))}
          </select>
        </div>

        {/* Zone Filter */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            {t('deliveryZone')}
          </label>
          <select
            value={filters.zone}
            onChange={(e) => handleFilterChange('zone', e.target.value)}
            className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">{t('allZones')}</option>
            {zones.map(zone => (
              <option key={zone.value} value={zone.value}>
                {zone.label}
              </option>
            ))}
          </select>
        </div>

        {/* Time Slot Filter */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            {t('timeSlot')}
          </label>
          <select
            value={filters.slot}
            onChange={(e) => handleFilterChange('slot', e.target.value)}
            className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">{t('allSlots')}</option>
            {timeSlots.map(slot => (
              <option key={slot.value} value={slot.value}>
                {slot.label}
              </option>
            ))}
          </select>
        </div>

        {/* Price Range */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            {t('priceRange')}
          </label>
          <div className="flex items-center space-x-2">
            <input
              type="number"
              value={filters.priceRange[0]}
              onChange={(e) => handleFilterChange('priceRange', [Number(e.target.value), filters.priceRange[1]])}
              className="w-20 border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="0"
            />
            <span className="text-gray-500">-</span>
            <input
              type="number"
              value={filters.priceRange[1]}
              onChange={(e) => handleFilterChange('priceRange', [filters.priceRange[0], Number(e.target.value)])}
              className="w-20 border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="1000"
            />
            <span className="text-gray-500 text-sm">NOK</span>
          </div>
        </div>

        {/* Clear Filters */}
        <button
          onClick={() => setFilters({
            category: '',
            zone: '',
            slot: '',
            partner: '',
            priceRange: [0, 1000]
          })}
          className="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors"
        >
          {t('clearFilters')}
        </button>
      </div>
    </div>
  )
}
