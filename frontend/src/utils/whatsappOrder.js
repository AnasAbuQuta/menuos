export function normalizeWhatsAppNumber(value) {
  const digits = String(value || '').replace(/\D/g, '').replace(/^00/, '')
  return /^\d{8,15}$/.test(digits) ? digits : ''
}

export function buildWhatsAppOrder({ restaurantName, lines, total, note, formatMoney }) {
  const itemLines = lines.map((line, index) => {
    const unit = formatMoney(line.item.price)
    const subtotal = formatMoney(Number(line.item.price) * line.quantity)
    return `${index + 1}. ${line.item.name} × ${line.quantity} — ${subtotal} (${unit} للوحدة)`
  })
  const parts = [
    `مرحباً، أريد طلب التالي من مطعم ${restaurantName}:`,
    '',
    ...itemLines,
    '',
    `الإجمالي: ${formatMoney(total)}`,
  ]
  if (note.trim()) parts.push('', 'ملاحظة:', note.trim())
  return parts.join('\n')
}

export function whatsappOrderUrl(number, message) {
  const normalized = normalizeWhatsAppNumber(number)
  return normalized ? `https://wa.me/${normalized}?text=${encodeURIComponent(message)}` : ''
}
