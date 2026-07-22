import {usePage} from "@inertiajs/vue3";
import {push} from "notivue";

export function truncateString(str, maxLength = 10) {
    if (str.length <= maxLength) {
        return str;
    }
    return str.slice(0, maxLength) + '...';
}

export function getCurrency() {
    return usePage().props.currency || usePage().props.businessSettings?.currency_symbol || 'S/';
}

export function numberFormat(number) {
    return parseFloat(Number(number || 0).toFixed(usePage().props.decimal_point));
}

export function cleanQuery(payload = {}) {
    return Object.fromEntries(Object.entries(payload).filter(([, value]) => {
        if (Array.isArray(value)) return value.length > 0;
        return value !== null && value !== undefined && value !== '';
    }));
}

export function formatDatetime(datetime) {
    if (!datetime) return '-';
    const date = new Date(datetime);
    if (Number.isNaN(date.getTime())) return '-';

    const settings = usePage().props.businessSettings || {};
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: settings.timezone || 'America/Lima',
        year: 'numeric',
        day: '2-digit',
        month: '2-digit',
        minute: '2-digit',
        hour: '2-digit',
        hour12: false,
    }).formatToParts(date).reduce((carry, part) => ({ ...carry, [part.type]: part.value }), {});

    const formattedDate = (settings.date_format || 'd/m/Y')
        .replace('d', parts.day)
        .replace('m', parts.month)
        .replace('Y', parts.year);
    const hourNumber = Number(parts.hour || 0);
    const formattedTime = settings.time_format === 'h:i A'
        ? `${String(hourNumber % 12 || 12).padStart(2, '0')}:${parts.minute} ${hourNumber >= 12 ? 'PM' : 'AM'}`
        : `${parts.hour}:${parts.minute}`;

    return `${formattedDate} ${formattedTime}`;
}

export function showToast() {
    if (usePage().props.flash.isSuccess) {
        push.success(usePage().props.flash.message)
    } else {
        push.error(usePage().props.flash.message)
    }
}
