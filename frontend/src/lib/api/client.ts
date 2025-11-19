import type {
  CampaignReport,
  AllCampaignsReport,
  DailyReport,
  ApiError
} from '../types';

const API_BASE_URL = '/api';

class ApiClient {
  private async request<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<T> {
    const url = `${API_BASE_URL}${endpoint}`;
    const response = await fetch(url, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...options.headers
      }
    });

    if (!response.ok) {
      const error: ApiError = await response.json().catch(() => ({
        error: `HTTP ${response.status}: ${response.statusText}`
      }));
      throw new Error(error.error || `HTTP ${response.status}`);
    }

    return response.json();
  }

  async getCampaignReport(
    campaignId: string,
    dateFrom?: string | null,
    dateTo?: string | null
  ): Promise<CampaignReport> {
    const params = new URLSearchParams();
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);

    const query = params.toString();
    return this.request<CampaignReport>(
      `/v1/reports/campaigns/${campaignId}${query ? `?${query}` : ''}`
    );
  }

  async getAllCampaignsReport(
    dateFrom?: string | null,
    dateTo?: string | null
  ): Promise<AllCampaignsReport> {
    const params = new URLSearchParams();
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);

    const query = params.toString();
    return this.request<AllCampaignsReport>(
      `/v1/reports/campaigns${query ? `?${query}` : ''}`
    );
  }

  async getDailyReport(
    dateFrom?: string | null,
    dateTo?: string | null
  ): Promise<DailyReport> {
    const params = new URLSearchParams();
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);

    const query = params.toString();
    return this.request<DailyReport>(
      `/v1/reports/daily${query ? `?${query}` : ''}`
    );
  }
}

export const apiClient = new ApiClient();

