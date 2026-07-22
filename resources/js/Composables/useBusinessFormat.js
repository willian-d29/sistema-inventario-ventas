import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useBusinessFormat() {
  const businessSettings = computed(() => usePage().props.businessSettings || {});

  function numberFormat(number) {
    const decimals = Number(usePage().props.decimal_point ?? businessSettings.value.decimal_point ?? 2);
    return Number(Number(number || 0).toFixed(decimals));
  }

  function currencySymbol() {
    return usePage().props.currency || businessSettings.value.currency_symbol || 'S/';
  }

  function money(number) {
    return `${currencySymbol()} ${numberFormat(Number(number || 0)).toFixed(Number(usePage().props.decimal_point ?? 2))}`;
  }

  function formatDateTime(datetime) {
    if (!datetime) return '-';

    return `${formatDate(datetime)} ${formatTime(datetime)}`.trim();
  }

  function formatDate(datetime) {
    if (!datetime) return '-';

    const date = new Date(datetime);
    if (Number.isNaN(date.getTime())) return '-';

    const parts = dateParts(date);
    return (businessSettings.value.date_format || 'd/m/Y')
      .replace('d', parts.day)
      .replace('m', parts.month)
      .replace('Y', parts.year);
  }

  function formatTime(datetime) {
    if (!datetime) return '-';

    const date = new Date(datetime);
    if (Number.isNaN(date.getTime())) return '-';

    const parts = dateParts(date);
    if (businessSettings.value.time_format === 'h:i A') {
      return `${parts.hour12}:${parts.minute} ${parts.period}`;
    }

    return `${parts.hour}:${parts.minute}`;
  }

  function dateParts(date) {
    const entries = new Intl.DateTimeFormat('en-CA', {
      timeZone: businessSettings.value.timezone || 'America/Lima',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    }).formatToParts(date).reduce((carry, part) => ({ ...carry, [part.type]: part.value }), {});

    const hourNumber = Number(entries.hour || 0);
    const hour12 = hourNumber % 12 || 12;

    return {
      year: entries.year,
      month: entries.month,
      day: entries.day,
      hour: entries.hour,
      minute: entries.minute,
      hour12: String(hour12).padStart(2, '0'),
      period: hourNumber >= 12 ? 'PM' : 'AM',
    };
  }

  return {
    businessSettings,
    currencySymbol,
    formatDate,
    formatDateTime,
    money,
    numberFormat,
  };
}
