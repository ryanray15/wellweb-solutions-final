const path = require("path");

module.exports = {
  entry: "./src/onboarding.js", // Path to your wallet.js file
  output: {
    filename: "bundle.js",
    path: path.resolve(__dirname, "public/dist"), // Output directory
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: "babel-loader", // Babel loader to transpile code
          options: {
            presets: ["@babel/preset-env"],
          },
        },
      },
    ],
  },
  mode: "development", // Use 'production' for a final build
  resolve: {
    modules: [path.resolve(__dirname, "node_modules")],
    alias: {
      "@stripe/connect-js": path.resolve(
        __dirname,
        "node_modules/@stripe/connect-js"
      ),
    },
  },
};
