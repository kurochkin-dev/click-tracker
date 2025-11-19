import { format, subDays, parseISO, isValid } from 'date-fns';

export function formatDate(date: string | Date): string {
  const dateObj = typeof date === 'string' ? parseISO(date) : date;
  if (!isValid(dateObj)) {
    return typeof date === 'string' ? date : date.toISOString();
  }
  return format(dateObj, 'MMM dd, yyyy');
}

export function getDefaultDateRange(): { from: string; to: string } {
  const today = new Date();
  const weekAgo = subDays(today, 7);
  return {
    from: format(weekAgo, 'yyyy-MM-dd'),
    to: format(today, 'yyyy-MM-dd')
  };
}

export function formatNumber(num: number): string {
  return new Intl.NumberFormat('en-US').format(num);
}

export function formatPercent(value: number): string {
  return `${(value * 100).toFixed(2)}%`;
}

