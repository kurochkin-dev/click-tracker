// Метрики одного объявления
export interface GoodStats {
  good_id: string;
  good_views: number;
  contact_reveals: number;
  profile_views: number;
  message_sends: number;
  unique_users: number;
}

// Суточная разбивка для одного объявления
export interface GoodDailyStats {
  date: string;
  good_views: number;
  contact_reveals: number;
  profile_views: number;
  message_sends: number;
  unique_users: number;
}

// Детальный отчёт по одному объявлению
export interface GoodReport {
  good_id: string;
  period: {
    from: string | null;
    to: string | null;
  };
  stats: GoodStats;
  daily: GoodDailyStats[];
}

// Сводный отчёт по всем объявлениям
export interface AllGoodsReport {
  period: {
    from: string | null;
    to: string | null;
  };
  goods: GoodStats[];
  total: {
    good_views: number;
    contact_reveals: number;
    profile_views: number;
    message_sends: number;
  };
}

// Суточная статистика
export interface DailyStats {
  date: string;
  total_events: number;
  good_views: number;
  contact_reveals: number;
  profile_views: number;
  message_sends: number;
}

export interface DailyReport {
  period: {
    from: string | null;
    to: string | null;
  };
  daily: DailyStats[];
}

// Гео-запись
export interface GeoEntry {
  country: string | null;
  city: string | null;
  events: number;
}

export interface GeoReport {
  period: {
    from: string | null;
    to: string | null;
  };
  action: string | null;
  geo: GeoEntry[];
}

export interface ApiError {
  error: string;
}
