import { Layout } from 'nextra-theme-docs'
import { Head } from 'nextra/components'
import { getPageMap } from 'nextra/page-map'
import 'nextra-theme-docs/style.css'
 
export const metadata = {
  title: {
    default: 'Đỗ Anh Minh',
    template: '%s | Đỗ Anh Minh'
  },
  description:
    'Ghi chép công khai về dạy học theo tinh thần Waldorf ở Hội An, hòa âm thực hành, làm vườn vừa sức và cộng đồng Đạp xe xuyên Việt.'
}
 
export default async function RootLayout({ children }) {
  return (
    <html
      // Content is Vietnamese; tell browsers and screen readers so.
      lang="vi"
      // Required to be set
      dir="ltr"
      // Suggested by `next-themes` package https://github.com/pacocoursey/next-themes#with-app
      suppressHydrationWarning
    >
      <Head
      // ... Your additional head options
      >
        {/* Your additional tags should be passed as `children` of `<Head>` element */}
      </Head>
      <body>
        <Layout
          pageMap={await getPageMap()}
          docsRepositoryBase="https://github.com/minhxuvi/minhxuvi.github.io/tree/main/content"
        >
          {children}
        </Layout>
      </body>
    </html>
  )
}