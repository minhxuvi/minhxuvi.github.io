import React from "react";
import { DocsThemeConfig } from "nextra-theme-docs";
import { useRouter } from "next/router";

const config: DocsThemeConfig = {
  logo: <span>Minh Mốc Meo</span>,
  project: {
    link: "https://github.com/minhxuvi/minhxuvi.github.io",
  },
  docsRepositoryBase:
    "https://github.com/minhxuvi/minhxuvi.github.io/tree/main",
  footer: {
    text: "Minh Mốc Meo",
  },
  useNextSeoProps() {
    const router = useRouter();
    const canonicalUrl = (
      `https://minh.xuvi.vn` + (router.asPath === "/" ? "" : router.asPath)
    ).split("?")[0];

    return {
      titleTemplate: "%s – MocMeo",
      canonical: canonicalUrl,
      twitter: {
        cardType: "summary_large_image",
        site: "@minhxuvi",
        handle: "@minhxuvi",
      },
    };
  },
  head: (
    <>
      <meta name="msapplication-TileColor" content="#fff" />
      <meta httpEquiv="Content-Language" content="vi" />
      <meta
        name="description"
        content="Minh Mốc Meo: Father, Engineer, Wanderer"
      />
      <meta property="og:title" content="Minh Mốc Meo" />
      <meta property="og:description" content="Father, Engineer, Wanderer" />
      <meta name="apple-mobile-web-app-title" content="Mốc Meo" />
    </>
  ),
};

export default config;
