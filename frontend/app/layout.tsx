import './globals.css';

import type { ReactNode } from 'react';
import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'Field Forecast',
  description: 'Compare sports odds in real time'
};

export default function RootLayout({ children }: { children: ReactNode }) {
  return (
    <html lang="en" className="dark">
      <body className="bg-zinc-950 text-zinc-100 antialiased">
        <div className="min-h-screen">{children}</div>
      </body>
    </html>
  );
}

