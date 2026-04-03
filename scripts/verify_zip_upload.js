const AdmZip = require('adm-zip');
const fs = require('fs');
const path = require('path');

// Simulation of the API logic
async function testZipLogic() {
    console.log("1. Creating dummy ZIP file...");
    const zip = new AdmZip();
    zip.addFile("index.html", Buffer.from("<html><body><h1>Hello SCORM</h1></body></html>", "utf8"));
    zip.addFile("imsmanifest.xml", Buffer.from("<manifest></manifest>", "utf8"));

    const zipName = "test_package.zip";
    const zipPath = path.join(__dirname, zipName);
    zip.writeZip(zipPath);
    console.log(`   Created: ${zipPath}`);

    console.log("2. Simulating Upload Extraction...");
    try {
        const uploadDir = path.join(__dirname, "temp_extraction");
        if (!fs.existsSync(uploadDir)) fs.mkdirSync(uploadDir);

        const extractDirName = `extracted_${Date.now()}`;
        const extractPath = path.join(uploadDir, extractDirName);

        console.log(`   Extracting to: ${extractPath}`);
        const unzip = new AdmZip(zipPath);
        unzip.extractAllTo(extractPath, true);

        console.log("3. Verifying Extracted Content...");
        const entries = fs.readdirSync(extractPath);
        console.log(`   Entries found: ${entries.join(", ")}`);

        if (entries.includes("index.html")) {
            console.log("   ✅ SUCCESS: index.html found!");
            console.log(`   Simulated URL: /uploads/.../${extractDirName}/index.html`);
        } else {
            console.error("   ❌ FAILURE: index.html not found.");
        }

        // Cleanup
        console.log("4. Cleanup...");
        fs.unlinkSync(zipPath);
        fs.rmSync(uploadDir, { recursive: true, force: true });
        console.log("   Cleanup done.");

    } catch (e) {
        console.error("   ❌ ERROR:", e);
    }
}

testZipLogic();
