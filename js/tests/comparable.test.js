/**
 * @jest-environment jsdom
 */
const fs = require('fs');
const path = require('path');

// Read the file content
const baseJsPath = path.resolve(__dirname, '../base.js');
const baseJsContent = fs.readFileSync(baseJsPath, 'utf8');

describe('Comparable', () => {
    beforeAll(() => {
        // Evaluate the script in the global context
        try {
            window.eval(baseJsContent);
        } catch (e) {
            console.error('Error evaluating base.js:', e);
        }
    });

    test('initializes correctly', () => {
        const c = new window.Comparable();
        expect(c.list).toEqual([]);
        expect(c.count).toBe(0);
        expect(c.length()).toBe(0);
    });

    test('can add items', () => {
        const c = new window.Comparable();
        c.add('key1', 'data1');
        expect(c.length()).toBe(1);
        expect(c.get(0).key).toBe('key1');
        expect(c.get(0).data).toBe('data1');
    });

    test('updates existing items', () => {
        const c = new window.Comparable();
        c.add('key1', 'data1');
        c.add('key1', 'data2');
        expect(c.length()).toBe(1);
        expect(c.get(0).key).toBe('key1');
        expect(c.get(0).data).toBe('data2');
    });

    test('can find items', () => {
        const c = new window.Comparable();
        c.add('key1', 'data1');
        c.add('key2', 'data2');

        expect(c.find('key1')).toBe('data1');
        expect(c.find('key2')).toBe('data2');
        expect(c.find('key3')).toBeUndefined();
    });

    test('can search items', () => {
        const c = new window.Comparable();
        c.add('key1', 'data1');
        c.add('key2', 'data2');

        expect(c.search('key1')).toBe(0);
        expect(c.search('key2')).toBe(1);
        expect(c.search('key3')).toBe(-1);
    });

    test('can get items by index', () => {
        const c = new window.Comparable();
        c.add('key1', 'data1');
        c.add('key2', 'data2');

        const item1 = c.get(0);
        expect(item1.key).toBe('key1');
        expect(item1.data).toBe('data1');

        const item2 = c.get(1);
        expect(item2.key).toBe('key2');
        expect(item2.data).toBe('data2');

        expect(c.get(2)).toBeUndefined();
    });

    test('CompItem compare works', () => {
        const c = new window.Comparable();
        c.add('key1', 'data1');
        c.add('key2', 'data2');

        const item1 = c.get(0);
        const item2 = c.get(1);

        // item1.key is 'key1', item2.key is 'key2'
        // 'key1' < 'key2' -> -1
        expect(item1.compare(item2)).toBe(-1);

        // 'key2' > 'key1' -> 1
        expect(item2.compare(item1)).toBe(1);

        expect(item1.compare(item1)).toBe(0);
    });

    test('CompItem equals works', () => {
        const c = new window.Comparable();
        c.add('key1', 'data1');

        const item = c.get(0);
        expect(item.equals('key1')).toBe(true);
        expect(item.equals('key2')).toBe(false);
    });

    test('can instantiate CompItem directly', () => {
        const item = new window.CompItem('key', 'data');
        expect(item.key).toBe('key');
        expect(item.data).toBe('data');
    });

    test('length reflects added items', () => {
        const c = new window.Comparable();
        expect(c.length()).toBe(0);
        c.add('k1', 'd1');
        expect(c.length()).toBe(1);
        c.add('k2', 'd2');
        expect(c.length()).toBe(2);
        c.add('k1', 'd3'); // update
        expect(c.length()).toBe(2);
    });
});
