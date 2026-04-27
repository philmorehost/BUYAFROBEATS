# BuyBeats 1.0 — Client & Operational Guide

This document provides a comprehensive overview of the **BuyBeats 1.0** platform, covering its unique auction mechanics, payment infrastructure, and administrative capabilities.

---

## 1. Core Concept: The "One-of-One" Exclusive Model
Unlike traditional beat marketplaces where many artists can license the same beat, BuyBeats operates on an **exclusive-only, one-of-one auction model**.
- Once a beat is sold, it is permanently removed from the site.
- The high-quality (HQ) file is only available to the winner for **24 hours**.
- To maintain absolute exclusivity, the platform automatically deletes the HQ file from the server after the 24-hour window, ensuring no copy remains online.

---

## 2. Frontend Experience (User/Buyer)

### A. Live Marketplace & Discovery
- **Auction Grid:** Users see all live auctions with real-time bidding status.
- **20-Second Sample Preview:** Buyers can listen to a 20-second preview of the beat directly on the landing page to gauge the vibe before committing to a bid.
- **Search & Filters:** Real-time filtering by genre (Afrobeats, Amapiano, Afro-Swing, etc.) and title search.
- **Leaderboard & Activity:** A live "Trending" leaderboard shows the hottest beats, and a "Live Activity" feed creates social proof by showing real-time bids from other users.

### B. The Auction Mechanics
- **30-Minute Timer:** The official auction clock for a beat starts only when the *first* bid is placed. This creates a sense of urgency.
- **Anti-Snipe Policy:** If a bid is placed in the final 2 minutes of an auction, the timer is automatically extended by an additional 2 minutes. This prevents "sniping" and ensures the highest bidder is truly the one who valued the beat most.
- **Real-Time Updates:** Bids and timer updates are pushed to all users instantly via Server-Sent Events (SSE), so they never have to refresh the page.

### C. Winning & Secure Payments
- **Win Notification:** The highest bidder receives an automated email immediately when the timer hits zero.
- **Plisio Crypto Gateway:** Payments are processed via Plisio, allowing buyers to pay securely with various cryptocurrencies.
- **Automated Delivery:** Once the payment is confirmed via the Plisio Webhook, the buyer receives a receipt and a secure, time-limited download link for the HQ WAV/MP3 file.

---

## 3. Backend Experience (Admin)

### A. Dashboard & Analytics
The Admin Dashboard provides a high-level view of the studio's performance, including active auctions, total bids, and recent sales.

### B. Beat Management (The "Drop")
- **Dual Upload System:** When listing a new beat, the Admin uploads two files:
    1.  **Main Audio (HQ):** The full, high-quality file for the buyer.
    2.  **Sample Audio:** A preview file that the system will automatically limit to a 20-second playback for public users.
- **Auction Parameters:** Admin sets the starting bid, BPM, Key, Genre, and Duration.

### C. Platform Settings
- **Identity & SEO:** Manage site titles, meta descriptions, and keywords for search engine optimization.
- **Payment Integration:** Securely manage the Plisio API Key. The system provides the absolute **Webhook URL** (Status URL) which the Admin simply copies and pastes into their Plisio dashboard to sync payments.
- **Email (SMTP):** Configure professional email delivery (Host, Port, User, Pass) to ensure bid notifications and receipts land in users' inboxes.
- **Code Injection:** Add Google Analytics, AdSense, or custom tracking scripts directly via the Header/Footer injection panels.

### D. Content Management (CMS)
- **Pages:** Create and edit custom pages like "About Us" or "Contact".
- **Legal:** Manage the Privacy Policy and Terms & Conditions.
- **FAQs:** A dedicated section to manage the frequently asked questions that appear in the interactive accordion on the frontend.

---

## 4. Technical Security & Best Practices
- **HMAC Verification:** All payment webhooks are verified using HMAC-SHA1 signatures to prevent fraudulent payment spoofing.
- **CSRF Protection:** All administrative and bidding forms are protected against Cross-Site Request Forgery.
- **Automated Cleanup:** A built-in cleanup service runs periodically to ensure "Sold" beats and their physical files are purged according to the 24-hour exclusivity policy.
