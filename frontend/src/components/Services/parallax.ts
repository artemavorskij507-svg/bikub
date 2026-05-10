export function calculateParallax(element: HTMLElement, intensity: number) {
  const rect = element.getBoundingClientRect()
  const centerX = rect.left + rect.width / 2
  const centerY = rect.top + rect.height / 2

  return (x: number, y: number) => {
    const ratioX = (x - centerX) / rect.width
    const ratioY = (y - centerY) / rect.height

    return {
      rotateX: ratioY * -intensity,
      rotateY: ratioX * intensity
    }
  }
}

export function getScrollOffset(element: HTMLElement) {
  const rect = element.getBoundingClientRect()
  const viewHeight = window.innerHeight || document.documentElement.clientHeight
  const ratio = (viewHeight - rect.top) / (viewHeight + rect.height)
  return Math.max(0, Math.min(1, ratio))
}

export function getMouseOffset(event: PointerEvent, element: HTMLElement) {
  const rect = element.getBoundingClientRect()
  return {
    x: event.clientX - rect.left,
    y: event.clientY - rect.top
  }
}
