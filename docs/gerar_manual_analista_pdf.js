const { execFileSync } = require("node:child_process");
const fs = require("node:fs");
const path = require("node:path");

const chromeCandidates = [
  "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe",
  "C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe",
  "C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe",
  "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe",
];

const chrome = chromeCandidates.find((candidate) => fs.existsSync(candidate));
if (!chrome) {
  throw new Error("Chrome ou Edge nao encontrado para exportar o PDF.");
}

const root = __dirname;
const htmlPath = path.join(root, "MANUAL_OPERACIONAL_ANALISTA.html");
const pdfPath = path.join(root, "MANUAL_OPERACIONAL_ANALISTA.pdf");
const userDataDir = path.join(root, ".chrome-pdf-manual-analista-profile");

if (!fs.existsSync(htmlPath)) {
  throw new Error(`HTML nao encontrado: ${htmlPath}`);
}

if (fs.existsSync(pdfPath)) {
  fs.rmSync(pdfPath, { force: true });
}

fs.mkdirSync(userDataDir, { recursive: true });

const fileUrl = `file:///${htmlPath.replace(/\\/g, "/")}`;

console.log(`Exportando HTML para PDF via ${chrome}...`);

execFileSync(chrome, [
  "--headless=new",
  "--disable-gpu",
  "--no-first-run",
  "--allow-file-access-from-files",
  `--user-data-dir=${userDataDir}`,
  "--print-to-pdf-no-header",
  `--print-to-pdf=${pdfPath}`,
  fileUrl,
], {
  stdio: "inherit",
  windowsHide: true,
});

if (!fs.existsSync(pdfPath)) {
  throw new Error("Falha ao gerar o arquivo PDF.");
}

const stats = fs.statSync(pdfPath);
if (stats.size < 50_000) {
  throw new Error(`PDF gerado parece pequeno demais (${stats.size} bytes).`);
}

console.log(`[OK] PDF do Manual do Analista gerado com sucesso: ${pdfPath} (${(stats.size / 1024).toFixed(1)} KB)`);
