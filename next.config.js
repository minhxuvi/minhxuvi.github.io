const withNextra = require("nextra")({
  theme: "nextra-theme-docs",
  themeConfig: "./theme.config.tsx",
});

module.exports = withNextra({
  // images: {
  //   unoptimized: true,
  // },
  images: {
    loader: "akamai",
    path: "",
  },
  i18n: {
    locales: ["vi-VN"],
    defaultLocale: "vi-VN",
  },
});
