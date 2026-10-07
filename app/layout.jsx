import { Layout, LastUpdated, Navbar } from 'nextra-theme-docs'
import { Head, Search } from 'nextra/components'
import { getPageMap } from 'nextra/page-map'
import { SiteMenuButton } from '../components/site-menu-button'
import 'nextra-theme-docs/style.css'
import './globals.css'
 
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
          navbar={
            <Navbar logo={<span className="site-title">Đỗ Anh Minh</span>}>
              <SiteMenuButton />
            </Navbar>
          }
          search={
            <Search
              placeholder="Tìm kiếm nội dung…"
              loading="Đang tải…"
              emptyResult="Không tìm thấy kết quả."
              errorText="Không tải được chỉ mục tìm kiếm."
            />
          }
          themeSwitch={{ dark: 'Tối', light: 'Sáng', system: 'Theo hệ thống' }}
          toc={{ title: 'Mục lục', backToTop: 'Lên đầu trang' }}
          editLink="Sửa trang này"
          feedback={{ content: 'Góp ý' }}
          lastUpdated={<LastUpdated locale="vi">Cập nhật lần cuối</LastUpdated>}
        >
          {children}
        </Layout>
      </body>
    </html>
  )
}
