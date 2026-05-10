import { useCallback, useEffect, useMemo, useRef, useState } from 'react'

export interface ParallaxConfig {
  intensity?: number
  friction?: number
}

export interface ParallaxState {
  rotateX: number
  rotateY: number
  translateZ: number
}

const DEFAULT_STATE: ParallaxState = { rotateX: 0, rotateY: 0, translateZ: 0 }

export function useMouseParallax({ intensity = 18, friction = 0.08 }: ParallaxConfig = {}) {
  const requestRef = useRef<number | null>(null)
  const targetRef = useRef<HTMLDivElement | null>(null)
  const [parallax, setParallax] = useState<ParallaxState>(DEFAULT_STATE)

  const animate = useCallback((target: ParallaxState) => {
    setParallax((prev) => ({
      rotateX: prev.rotateX + (target.rotateX - prev.rotateX) * friction,
      rotateY: prev.rotateY + (target.rotateY - prev.rotateY) * friction,
      translateZ: prev.translateZ + (target.translateZ - prev.translateZ) * friction
    }))
  }, [friction])

  const onPointerMove = useCallback((event: PointerEvent) => {
    if (!targetRef.current) return
    const rect = targetRef.current.getBoundingClientRect()
    const offsetX = event.clientX - rect.left
    const offsetY = event.clientY - rect.top
    const ratioX = offsetX / rect.width - 0.5
    const ratioY = offsetY / rect.height - 0.5

    const targetState: ParallaxState = {
      rotateX: ratioY * intensity,
      rotateY: ratioX * -intensity,
      translateZ: Math.sqrt(Math.abs(ratioX * ratioY)) * 22
    }

    cancelAnimationFrame(requestRef.current ?? 0)
    requestRef.current = requestAnimationFrame(() => animate(targetState))
  }, [animate, intensity])

  const reset = useCallback(() => {
    cancelAnimationFrame(requestRef.current ?? 0)
    requestRef.current = requestAnimationFrame(() => animate(DEFAULT_STATE))
  }, [animate])

  useEffect(() => {
    const node = targetRef.current
    if (!node) return

    node.addEventListener('pointermove', onPointerMove)
    node.addEventListener('pointerleave', reset)

    return () => {
      node.removeEventListener('pointermove', onPointerMove)
      node.removeEventListener('pointerleave', reset)
      cancelAnimationFrame(requestRef.current ?? 0)
    }
  }, [onPointerMove, reset])

  const transform = useMemo(() => ({
    transform: `perspective(1200px) rotateX(${parallax.rotateX.toFixed(2)}deg) rotateY(${parallax.rotateY.toFixed(2)}deg) translateZ(${parallax.translateZ.toFixed(2)}px)`
  }), [parallax])

  return { ref: targetRef, transform, reset, state: parallax }
}
