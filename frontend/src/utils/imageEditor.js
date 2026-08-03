export const IMAGE_PROFILES = {
  logo: { label: 'Logo', aspectRatio: 1, ratios: [{ label: 'Square', value: 1 }], maxWidth: 800, maxHeight: 800 },
  cover: { label: 'Cover image', aspectRatio: 16 / 9, ratios: [{ label: '16:9', value: 16 / 9 }, { label: '3:1', value: 3 }], maxWidth: 1920, maxHeight: 1080 },
  menuItem: { label: 'Menu item image', aspectRatio: 4 / 3, ratios: [{ label: '4:3', value: 4 / 3 }, { label: 'Square', value: 1 }], maxWidth: 1200, maxHeight: 1200 },
}

export const MAX_IMAGE_BYTES = 2 * 1024 * 1024
export const SUPPORTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp']

export function validateImageFile(file) {
  if (!file || !SUPPORTED_IMAGE_TYPES.includes(file.type)) return 'Choose a JPG, PNG, or WebP image.'
  if (file.size > MAX_IMAGE_BYTES) return 'Image size must not exceed 2 MB.'
  return ''
}

export function fitDimensions(width, height, maxWidth, maxHeight) {
  const scale = Math.min(1, maxWidth / width, maxHeight / height)
  return { width: Math.max(1, Math.round(width * scale)), height: Math.max(1, Math.round(height * scale)) }
}

function canvasBlob(canvas, type, quality) {
  return new Promise((resolve, reject) => canvas.toBlob((blob) => blob ? resolve(blob) : reject(new Error('Could not process this image.')), type, quality))
}

export async function processCroppedCanvas(sourceCanvas, sourceFile, profile) {
  const dimensions = fitDimensions(sourceCanvas.width, sourceCanvas.height, profile.maxWidth, profile.maxHeight)
  const output = document.createElement('canvas')
  output.width = dimensions.width
  output.height = dimensions.height
  output.getContext('2d').drawImage(sourceCanvas, 0, 0, dimensions.width, dimensions.height)
  const preserveTransparency = sourceFile.type === 'image/png'
  const type = preserveTransparency ? 'image/png' : 'image/webp'
  const blob = await canvasBlob(output, type, 0.84)
  if (blob.size > MAX_IMAGE_BYTES) throw new Error('The processed image is still larger than 2 MB. Try a smaller crop.')
  const base = sourceFile.name.replace(/\.[^.]+$/, '') || 'image'
  return new File([blob], `${base}-edited.${preserveTransparency ? 'png' : 'webp'}`, { type, lastModified: Date.now() })
}

export function formatFileSize(bytes) {
  return bytes < 1024 * 1024 ? `${Math.round(bytes / 1024)} KB` : `${(bytes / 1024 / 1024).toFixed(1)} MB`
}
