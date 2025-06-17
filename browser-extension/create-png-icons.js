// Simple icon creator using pure JavaScript
const fs = require('fs');

// Create a very basic PNG file using raw binary data
// This creates a minimal 1x1 pixel PNG that we'll scale up
function createMinimalPNG(width, height, r, g, b) {
    // PNG signature
    const pngSignature = Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]);
    
    // IHDR chunk
    const ihdrData = Buffer.alloc(13);
    ihdrData.writeUInt32BE(width, 0);  // Width
    ihdrData.writeUInt32BE(height, 4); // Height
    ihdrData[8] = 8;   // Bit depth
    ihdrData[9] = 2;   // Color type (RGB)
    ihdrData[10] = 0;  // Compression
    ihdrData[11] = 0;  // Filter
    ihdrData[12] = 0;  // Interlace
    
    const ihdrChunk = createChunk('IHDR', ihdrData);
    
    // Simple pixel data - create a solid color image
    const bytesPerPixel = 3; // RGB
    const rowSize = width * bytesPerPixel + 1; // +1 for filter byte
    const pixelData = Buffer.alloc(height * rowSize);
    
    for (let y = 0; y < height; y++) {
        const rowStart = y * rowSize;
        pixelData[rowStart] = 0; // Filter type (None)
        
        for (let x = 0; x < width; x++) {
            const pixelStart = rowStart + 1 + x * bytesPerPixel;
            pixelData[pixelStart] = r;     // Red
            pixelData[pixelStart + 1] = g; // Green
            pixelData[pixelStart + 2] = b; // Blue
        }
    }
    
    // Compress the pixel data (simplified - just use raw data)
    const zlib = require('zlib');
    const compressedData = zlib.deflateSync(pixelData);
    const idatChunk = createChunk('IDAT', compressedData);
    
    // IEND chunk
    const iendChunk = createChunk('IEND', Buffer.alloc(0));
    
    return Buffer.concat([pngSignature, ihdrChunk, idatChunk, iendChunk]);
}

function createChunk(type, data) {
    const length = Buffer.alloc(4);
    length.writeUInt32BE(data.length, 0);
    
    const typeBuffer = Buffer.from(type, 'ascii');
    const crc = require('zlib').crc32(Buffer.concat([typeBuffer, data]));
    const crcBuffer = Buffer.alloc(4);
    crcBuffer.writeUInt32BE(crc, 0);
    
    return Buffer.concat([length, typeBuffer, data, crcBuffer]);
}

console.log('Creating basic PNG icons...');

try {
    // Create blue square icons
    const sizes = [16, 32, 48, 128];
    const blueColor = [37, 99, 235]; // #2563eb in RGB
    
    sizes.forEach(size => {
        const pngBuffer = createMinimalPNG(size, size, ...blueColor);
        fs.writeFileSync(`assets/icon${size}.png`, pngBuffer);
        console.log(`✅ Created icon${size}.png (${size}x${size})`);
    });
    
    console.log('\\n🎉 PNG icons created successfully!');
    console.log('The extension should now load properly.');
    
} catch (error) {
    console.error('PNG creation failed:', error.message);
    console.log('\\n🔄 Fallback: Using simpler approach...');
    
    // Fallback: Create the smallest possible valid PNG
    const tinyPNG = Buffer.from([
        137, 80, 78, 71, 13, 10, 26, 10, // PNG signature
        0, 0, 0, 13, // IHDR length
        73, 72, 68, 82, // IHDR
        0, 0, 0, 1, 0, 0, 0, 1, 8, 2, 0, 0, 0, // 1x1 RGB
        144, 119, 83, 222, // IHDR CRC
        0, 0, 0, 12, // IDAT length
        73, 68, 65, 84, // IDAT
        120, 156, 98, 98, 250, 207, 0, 0, 0, 4, 0, 1, // Compressed pixel data
        221, 204, 78, 91, // IDAT CRC
        0, 0, 0, 0, // IEND length
        73, 69, 78, 68, // IEND
        174, 66, 96, 130 // IEND CRC
    ]);
    
    [16, 32, 48, 128].forEach(size => {
        fs.writeFileSync(`assets/icon${size}.png`, tinyPNG);
        console.log(`⚠️  Created basic icon${size}.png (1x1 fallback)`);
    });
}
