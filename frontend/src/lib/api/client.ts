import type { GoodReport, AllGoodsReport, DailyReport, GeoReport, ApiError } from '../types';

const API_BASE_URL = '/api';

class ApiClient {
  private async request<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
    const url = `${API_BASE_URL}${endpoint}`;
    const response = await fetch(url, {
      ...options,
      headers: { 'Content-Type': 'application/json', ...options.headers }
    });

    if (!response.ok) {
      const error: ApiError = await response.json().catch(() => ({
        error: `HTTP ${response.status}: ${response.statusText}`
      }));
      throw new Error(error.error || `HTTP ${response.status}`);
    }

    return response.json();
  }

  private buildQuery(dateFrom?: string | null, dateTo?: string | null, extra?: Record<string, string>): string {
    const params = new URLSearchParams();
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    if (extra) Object.entries(extra).forEach(([k, v]) => params.append(k, v));
    const q = params.toString();
    return q ? `?${q}` : '';
  }

  async getGoodReport(goodId: string, dateFrom?: string | null, dateTo?: string | null): Promise<GoodReport> {
    return this.request<GoodReport>(`/v1/reports/goods/${goodId}${this.buildQuery(dateFrom, dateTo)}`);
  }

  async getAllGoodsReport(dateFrom?: string | null, dateTo?: string | null): Promise<AllGoodsReport> {
    return this.request<AllGoodsReport>(`/v1/reports/goods${this.buildQuery(dateFrom, dateTo)}`);
  }

  async getDailyReport(dateFrom?: string | null, dateTo?: string | null): Promise<DailyReport> {
    return this.request<DailyReport>(`/v1/reports/daily${this.buildQuery(dateFrom, dateTo)}`);
  }

  async getGeoReport(
    dateFrom?: string | null,
    dateTo?: string | null,
    action?: string | null
  ): Promise<GeoReport> {
    const extra = action ? { action } : undefined;
    return this.request<GeoReport>(`/v1/reports/geo${this.buildQuery(dateFrom, dateTo, extra)}`);
  }
}

export const apiClient = new ApiClient();
