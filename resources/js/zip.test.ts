import { describe, expect, it } from 'vitest';
import { crc32, createZip } from './zip';

// Reads the archive back through the central directory, like an unzip tool.
function readZip(zip: Uint8Array): Record<string, string> {
  const view = new DataView(zip.buffer, zip.byteOffset, zip.byteLength);
  const decoder = new TextDecoder();
  const end = zip.length - 22;
  expect(view.getUint32(end, true)).toBe(0x06054b50);
  const count = view.getUint16(end + 10, true);
  let pos = view.getUint32(end + 16, true);
  const files: Record<string, string> = {};

  for (let i = 0; i < count; i++) {
    expect(view.getUint32(pos, true)).toBe(0x02014b50);
    const crc = view.getUint32(pos + 16, true);
    const size = view.getUint32(pos + 24, true);
    const nameLength = view.getUint16(pos + 28, true);
    const offset = view.getUint32(pos + 42, true);
    const name = decoder.decode(zip.subarray(pos + 46, pos + 46 + nameLength));

    expect(view.getUint32(offset, true)).toBe(0x04034b50);
    const start = offset + 30 + view.getUint16(offset + 26, true);
    const data = zip.subarray(start, start + size);
    expect(crc32(data)).toBe(crc);
    files[name] = decoder.decode(data);
    pos += 46 + nameLength;
  }
  return files;
}

describe('crc32', () => {
  it('matches the standard check value', () => {
    expect(crc32(new TextEncoder().encode('123456789'))).toBe(0xcbf43926);
    expect(crc32(new Uint8Array())).toBe(0);
  });
});

describe('createZip', () => {
  it('round-trips text files including non-ASCII content', () => {
    const files = {
      'primitives.tokens.json': '{\n  "a": 1\n}\n',
      'tokens.css': ':root { --x: "Größe"; }\n',
    };
    const zip = createZip(
      Object.entries(files).map(([name, content]) => ({ name, content })),
      new Date(2026, 9, 30, 12, 0, 0)
    );
    expect(readZip(zip)).toEqual(files);
  });

  it('writes an empty archive', () => {
    expect(readZip(createZip([]))).toEqual({});
  });
});
