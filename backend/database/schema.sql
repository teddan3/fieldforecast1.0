-- Field Forecast 1.0
-- MySQL 8+ schema for sports odds aggregation + comparison
-- Notes:
-- - Use utf8mb4 for full Unicode support
-- - Assumes InnoDB and foreign key checks enabled
-- - odds_latest is the fast-read table; odds_snapshots keeps history

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(191) NOT NULL,
  email VARCHAR(191) NOT NULL,
  password VARCHAR(255) NOT NULL,
  remember_token VARCHAR(100) NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookmakers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(191) NOT NULL,
  provider_key VARCHAR(191) NULL,
  logo_url VARCHAR(500) NULL,
  affiliate_url VARCHAR(1000) NOT NULL DEFAULT '',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY bookmakers_name_unique (name),
  UNIQUE KEY bookmakers_provider_key_unique (provider_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Used to track affiliate clicks (for admin analytics + attribution)
CREATE TABLE IF NOT EXISTS bookmaker_clicks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULL,
  bookmaker_id BIGINT UNSIGNED NOT NULL,
  match_id BIGINT UNSIGNED NULL,
  outcome ENUM('home', 'draw', 'away') NULL,
  created_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  PRIMARY KEY (id),
  KEY bookmaker_clicks_user_id_idx (user_id),
  KEY bookmaker_clicks_bookmaker_id_idx (bookmaker_id),
  KEY bookmaker_clicks_match_id_idx (match_id),
  CONSTRAINT bookmaker_clicks_bookmaker_fk FOREIGN KEY (bookmaker_id) REFERENCES bookmakers (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_credentials (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  provider_name VARCHAR(191) NOT NULL,
  api_key VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY api_credentials_provider_name_unique (provider_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sports (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(191) NOT NULL,
  slug VARCHAR(191) NOT NULL,
  provider_sport_key VARCHAR(191) NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY sports_slug_unique (slug),
  UNIQUE KEY sports_provider_sport_key_unique (provider_sport_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leagues (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sport_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(191) NOT NULL,
  slug VARCHAR(191) NOT NULL,
  country VARCHAR(191) NULL,
  provider_league_key VARCHAR(191) NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY leagues_slug_unique (slug),
  KEY leagues_sport_id_idx (sport_id),
  CONSTRAINT leagues_sport_fk FOREIGN KEY (sport_id) REFERENCES sports (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teams (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(191) NOT NULL,
  slug VARCHAR(191) NOT NULL,
  country VARCHAR(191) NULL,
  provider_team_key VARCHAR(191) NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY teams_slug_unique (slug),
  UNIQUE KEY teams_provider_team_key_unique (provider_team_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Matches are domain entities. Odds snapshots link to match_id.
CREATE TABLE IF NOT EXISTS matches (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  league_id BIGINT UNSIGNED NOT NULL,
  home_team_id BIGINT UNSIGNED NOT NULL,
  away_team_id BIGINT UNSIGNED NOT NULL,
  start_time DATETIME NOT NULL,
  status ENUM('scheduled','in_progress','finished') NOT NULL DEFAULT 'scheduled',
  external_match_id VARCHAR(191) NULL,
  source_provider VARCHAR(191) NOT NULL DEFAULT 'the_odds_api',
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY matches_provider_external_unique (source_provider, external_match_id),
  KEY matches_league_id_start_time_idx (league_id, start_time),
  KEY matches_home_away_idx (home_team_id, away_team_id),
  CONSTRAINT matches_league_fk FOREIGN KEY (league_id) REFERENCES leagues (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT matches_home_team_fk FOREIGN KEY (home_team_id) REFERENCES teams (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT matches_away_team_fk FOREIGN KEY (away_team_id) REFERENCES teams (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Latest odds table for fast reads (used by comparison UI)
CREATE TABLE IF NOT EXISTS odds_latest (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  match_id BIGINT UNSIGNED NOT NULL,
  league_id BIGINT UNSIGNED NOT NULL,
  home_team_id BIGINT UNSIGNED NOT NULL,
  away_team_id BIGINT UNSIGNED NOT NULL,
  bookmaker_id BIGINT UNSIGNED NOT NULL,
  odds_type VARCHAR(50) NOT NULL DEFAULT '1x2',
  home_odds DECIMAL(10,4) NOT NULL,
  draw_odds DECIMAL(10,4) NULL,
  away_odds DECIMAL(10,4) NOT NULL,
  captured_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  updated_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  source_provider VARCHAR(191) NOT NULL DEFAULT 'the_odds_api',
  PRIMARY KEY (id),
  UNIQUE KEY odds_latest_match_bookmaker_odds_type_unique (match_id, bookmaker_id, odds_type),
  KEY odds_latest_league_captured_idx (league_id, captured_at),
  KEY odds_latest_match_captured_idx (match_id, captured_at),
  KEY odds_latest_bookmaker_captured_idx (bookmaker_id, captured_at),
  CONSTRAINT odds_latest_match_fk FOREIGN KEY (match_id) REFERENCES matches (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT odds_latest_bookmaker_fk FOREIGN KEY (bookmaker_id) REFERENCES bookmakers (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Full history of odds for trend graphs + recalculation
CREATE TABLE IF NOT EXISTS odds_snapshots (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  match_id BIGINT UNSIGNED NOT NULL,
  league_id BIGINT UNSIGNED NOT NULL,
  home_team_id BIGINT UNSIGNED NOT NULL,
  away_team_id BIGINT UNSIGNED NOT NULL,
  bookmaker_id BIGINT UNSIGNED NOT NULL,
  odds_type VARCHAR(50) NOT NULL DEFAULT '1x2',
  home_odds DECIMAL(10,4) NOT NULL,
  draw_odds DECIMAL(10,4) NULL,
  away_odds DECIMAL(10,4) NOT NULL,
  captured_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  source_provider VARCHAR(191) NOT NULL DEFAULT 'the_odds_api',
  PRIMARY KEY (id),
  KEY odds_snapshots_match_captured_idx (match_id, captured_at),
  KEY odds_snapshots_bookmaker_match_idx (bookmaker_id, match_id, captured_at),
  KEY odds_snapshots_league_captured_idx (league_id, captured_at),
  CONSTRAINT odds_snapshots_match_fk FOREIGN KEY (match_id) REFERENCES matches (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT odds_snapshots_bookmaker_fk FOREIGN KEY (bookmaker_id) REFERENCES bookmakers (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Value bet insights computed from odds_latest at a specific captured_at
CREATE TABLE IF NOT EXISTS value_bet_insights (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  match_id BIGINT UNSIGNED NOT NULL,
  league_id BIGINT UNSIGNED NOT NULL,
  bookmaker_id BIGINT UNSIGNED NOT NULL,
  odds_type VARCHAR(50) NOT NULL DEFAULT '1x2',
  outcome ENUM('home','draw','away') NOT NULL,
  bookmaker_odds DECIMAL(10,4) NOT NULL,
  market_average_odds DECIMAL(10,4) NOT NULL,
  implied_probability DECIMAL(12,10) NOT NULL,
  market_implied_probability DECIMAL(12,10) NOT NULL,
  expected_value_profit DECIMAL(12,6) NOT NULL, -- EV per 1 unit stake in decimal-odds terms: p*odds - 1
  captured_at TIMESTAMP(6) NOT NULL,
  created_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (id),
  UNIQUE KEY value_bet_insights_match_bookmaker_outcome_captured_unique (match_id, bookmaker_id, outcome, captured_at),
  KEY value_bet_insights_match_captured_idx (match_id, captured_at),
  KEY value_bet_insights_bookmaker_captured_idx (bookmaker_id, captured_at),
  CONSTRAINT value_bet_insights_match_fk FOREIGN KEY (match_id) REFERENCES matches (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT value_bet_insights_bookmaker_fk FOREIGN KEY (bookmaker_id) REFERENCES bookmakers (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Arbitrage insights computed from best odds across bookmakers
CREATE TABLE IF NOT EXISTS arbitrage_insights (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  match_id BIGINT UNSIGNED NOT NULL,
  league_id BIGINT UNSIGNED NOT NULL,
  odds_type VARCHAR(50) NOT NULL DEFAULT '1x2',
  home_bookmaker_id BIGINT UNSIGNED NOT NULL,
  draw_bookmaker_id BIGINT UNSIGNED NOT NULL,
  away_bookmaker_id BIGINT UNSIGNED NOT NULL,
  home_odds DECIMAL(10,4) NOT NULL,
  draw_odds DECIMAL(10,4) NULL,
  away_odds DECIMAL(10,4) NOT NULL,
  implied_probability_sum DECIMAL(12,10) NOT NULL, -- sum of inverse best odds
  profit_percentage DECIMAL(12,6) NOT NULL, -- (1 - sum) * 100
  stake_home_ratio DECIMAL(12,10) NOT NULL,
  stake_draw_ratio DECIMAL(12,10) NULL,
  stake_away_ratio DECIMAL(12,10) NOT NULL,
  captured_at TIMESTAMP(6) NOT NULL,
  created_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (id),
  UNIQUE KEY arbitrage_insights_match_captured_unique (match_id, captured_at),
  KEY arbitrage_insights_match_captured_idx (match_id, captured_at),
  CONSTRAINT arbitrage_insights_match_fk FOREIGN KEY (match_id) REFERENCES matches (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT arbitrage_insights_home_bookmaker_fk FOREIGN KEY (home_bookmaker_id) REFERENCES bookmakers (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT arbitrage_insights_draw_bookmaker_fk FOREIGN KEY (draw_bookmaker_id) REFERENCES bookmakers (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT arbitrage_insights_away_bookmaker_fk FOREIGN KEY (away_bookmaker_id) REFERENCES bookmakers (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_favorites (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  favorite_type ENUM('team','league','sport') NOT NULL,
  favorite_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY user_favorites_user_type_id_unique (user_id, favorite_type, favorite_id),
  KEY user_favorites_user_id_idx (user_id),
  CONSTRAINT user_favorites_user_fk FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_bookmaker_preferences (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  bookmaker_id BIGINT UNSIGNED NOT NULL,
  priority INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY user_bookmaker_preferences_user_bookmaker_unique (user_id, bookmaker_id),
  KEY user_bookmaker_preferences_user_id_idx (user_id),
  CONSTRAINT user_bookmaker_preferences_user_fk FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT user_bookmaker_preferences_bookmaker_fk FOREIGN KEY (bookmaker_id) REFERENCES bookmakers (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Featured matches can be selected by admin for home page / marketing
CREATE TABLE IF NOT EXISTS featured_matches (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  match_id BIGINT UNSIGNED NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY featured_matches_match_unique (match_id),
  CONSTRAINT featured_matches_match_fk FOREIGN KEY (match_id) REFERENCES matches (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Minimal SEO-friendly blog module (dynamic pages: /blog/[slug])
CREATE TABLE IF NOT EXISTS posts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  excerpt TEXT NULL,
  content MEDIUMTEXT NOT NULL,
  published_at DATETIME NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 0,
  cover_image_url VARCHAR(500) NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY posts_slug_unique (slug),
  KEY posts_published_at_idx (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Suggested recommended indexes for filtering/searching:
-- - odds_latest captured_at is indexed via composite keys
-- - matches are indexed on (league_id, start_time)
-- - For full-text search of team names, consider FULLTEXT indexes later

