export interface CampaignStats {
  campaign_id: string;
  clicks: number;
  impressions: number;
  unique_users: number;
  ctr: number;
}

export interface CampaignDailyStats {
  date: string;
  clicks: number;
  impressions: number;
  unique_users: number;
}

export interface CampaignReport {
  campaign_id: string;
  period: {
    from: string | null;
    to: string | null;
  };
  stats: CampaignStats;
  daily: CampaignDailyStats[];
}

export interface AllCampaignsReport {
  period: {
    from: string | null;
    to: string | null;
  };
  campaigns: CampaignStats[];
  total: {
    clicks: number;
    impressions: number;
    unique_users: number;
  };
}

export interface DailyStats {
  date: string;
  total_events: number;
  clicks: number;
  impressions: number;
}

export interface DailyReport {
  period: {
    from: string | null;
    to: string | null;
  };
  daily: DailyStats[];
}

export interface ApiError {
  error: string;
}

