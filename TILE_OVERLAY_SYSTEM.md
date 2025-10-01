# Tile Overlay System - Multi-Layer Grid

## Overview
Updated the grid system to support multi-layer placement where tiles act as the background layer and items, storage, and decorations overlay on top with transparent backgrounds.

## Key Changes

### 1. **UI Restructuring**
- **Moved Grid Controls** to left sidebar panel (below Available Items)
- **Removed controls section** from top of grid
- **Full-width buttons** in left panel (Save Grid, Load Grid, Clear Grid)
- **Grid now displays in full width** without top controls blocking view

### 2. **Multi-Layer System**

#### Layer Hierarchy (bottom to top):
1. **Background Layer**: Tiles (colored grid cells, no DOM elements)
2. **Overlay Layer**: Items, Storage, Decorations (transparent elements with icons)

#### Tile Behavior:
- **Background only** - changes cell background color
- **No DOM element** - just CSS styling on grid cells
- **Always 1×1** - each tile occupies single cell
- **Can be overlaid** - items/storage/decorations can be placed on tiles
- **Click to remove** - clicking empty cell with tile removes the tile

#### Item/Storage/Decoration Behavior:
- **Transparent background** - no colored overlay
- **Icon only** - large centered emoji/icon with shadow
- **Overlay tiles** - can be placed on top of tiles
- **Multi-cell support** - can span multiple grid cells
- **Click to remove** - clicking item removes it (leaves tile underneath)

### 3. **Updated Functions**

#### `canPlaceItem(x, y, width, height, itemType)`
- Now accepts `itemType` parameter
- **Tiles as background**: Allows items/storage/decorations to be placed on tiles
- **Collision detection**: Only blocks placement if cell occupied by non-tile items
- **Tile stacking prevention**: Cannot place tile on existing tile

```javascript
// Before: Blocked all occupied cells
if (cell.occupied) return false;

// After: Allows placement on tiles
if (cell.occupied && cell.itemType !== 'tile') return false;
if (itemType === 'tile' && cell.itemType === 'tile') return false;
```

#### `clearGridArea(x, y, width, height, keepTiles = true)`
- New parameter `keepTiles` (default: true)
- **Preserves tile background** when removing items
- **Only clears overlay items** (items/storage/decorations)
- **Full clear option** for database clear operations

```javascript
// Keeps tiles when removing items on top
if (keepTiles && grid[y][x].itemType === 'tile') {
    grid[y][x].occupied = true; // Keep tile
    continue;
}
```

#### `placeItem(x, y, item)`
Updated to handle transparency:
- **Tiles**: Change cell background color only
- **Items/Storage/Decorations**: Create transparent overlay elements
- **No background styling** for overlay items
- **Icon with shadow** for visibility on any tile color

#### `loadGrid()`
- **Sorting by layer**: Loads tiles first, then overlay items
- **Proper stacking**: Ensures tiles render before items placed on top

```javascript
const sortedItems = [...result.items].sort((a, b) => {
    if (a.item_type === 'tile' && b.item_type !== 'tile') return -1;
    if (a.item_type !== 'tile' && b.item_type === 'tile') return 1;
    return 0;
});
```

### 4. **CSS Updates**

#### Transparent Placed Items:
```css
.placed-item {
    background: transparent; /* Was: gradient background */
    box-shadow: none; /* No shadow */
    pointer-events: auto; /* Still clickable */
}
```

#### Icon Visibility:
```css
.placed-item-icon-only {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.5));
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.8);
    /* Ensures icons visible on any tile color */
}
```

#### Hover Effects:
```css
.placed-item:hover {
    transform: scale(1.05);
    filter: brightness(1.2); /* Was: box-shadow */
    z-index: 100;
}
```

#### Grid Controls Panel:
```css
.grid-controls-panel {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 2px solid #667eea;
}

.btn-block {
    width: 100%;
    margin-bottom: 8px;
}
```

### 5. **HTML Structure**

#### Before:
```html
<div class="grid-container">
    <div class="controls">
        <button>Save</button>
        <button>Load</button>
        <button>Clear</button>
    </div>
    <div class="grid-wrapper">...</div>
</div>
```

#### After:
```html
<div class="left-panel">
    <h2>Available Items</h2>
    <div id="itemsList">...</div>
    
    <div class="grid-controls-panel">
        <h3>Grid Controls</h3>
        <button class="btn-block">💾 Save Grid</button>
        <button class="btn-block">📂 Load Grid</button>
        <button class="btn-block">🗑️ Clear Grid</button>
    </div>
</div>

<div class="grid-container">
    <div class="grid-wrapper">
        <div class="grid">...</div>
    </div>
</div>
```

## Usage Examples

### Example 1: Place Grass Tile, Then Item on Top
1. **Click "Grass" tile** → Tile selected
2. **Click grid cell** → Cell turns green (background)
3. **Press ESC** → Deselect tile
4. **Click "Sword" item** → Sword selected
5. **Click same green cell** → Sword appears on grass background
6. **Result**: Sword icon visible on green grass tile

### Example 2: Remove Item but Keep Tile
1. **Grid has**: Green tile + Sword on top
2. **Click Sword icon** → Sword removed
3. **Result**: Green tile remains as background
4. **Click green cell again** → Tile also removed

### Example 3: Large Storage on Multiple Tiles
1. **Place several grass tiles** in 3×3 area
2. **Click "Chest (3×3)"** from Storage panel
3. **Click top-left tile** → Chest appears over 3×3 grass area
4. **Result**: Chest icon overlays all 9 grass tiles

### Example 4: Mix Different Tiles
1. Place **Grass** tile at [0,0]
2. Place **Stone** tile at [1,0]
3. Place **Wood** tile at [2,0]
4. Click **Axe (2×1)** from items
5. Place Axe starting at [0,0]
6. **Result**: Axe overlays both Grass and Stone tiles

## Technical Details

### Grid State Management

Each grid cell now tracks:
```javascript
{
    occupied: true/false,
    itemId: number,
    itemType: 'tile' | 'item' | 'storage' | 'decoration'
}
```

**Occupied Status**:
- `occupied: true, itemType: 'tile'` = Tile background (allows overlay)
- `occupied: true, itemType: 'item'` = Item placed (blocks placement)
- `occupied: false` = Empty cell

### Placement Logic

**Placing a Tile**:
- ✅ Can place on empty cell
- ❌ Cannot place on existing tile
- ❌ Cannot place on cell with item/storage/decoration

**Placing Item/Storage/Decoration**:
- ✅ Can place on empty cell
- ✅ Can place on tile (overlays it)
- ❌ Cannot place on cell with another item/storage/decoration

### Z-Index Layering
- **Tiles**: z-index 0 (cell background, no element)
- **Items/Storage/Decorations**: z-index 10
- **Hover state**: z-index 100
- **Dragging**: z-index 1000
- **UI Elements**: z-index 10000

## Benefits

1. **Visual Depth**: Grid looks more realistic with layered items on tile backgrounds
2. **Flexible Design**: Users can create custom floor patterns with items on top
3. **Better Realism**: Mimics real-world where items sit on floor/ground
4. **Clear Visibility**: Transparent items with shadows work on any tile color
5. **Full Grid View**: Moving controls to sidebar maximizes grid visibility
6. **Easier Placement**: Full-width grid makes it easier to see and plan layouts

## Testing Checklist

- [x] Tiles change cell background color
- [x] Items have transparent background
- [x] Items can be placed on tiles
- [x] Icons visible on all tile colors (shadow effect)
- [x] Removing item keeps tile underneath
- [x] Removing tile (click empty cell) works
- [x] Multi-cell items overlay multiple tiles
- [x] Cannot place item on another item
- [x] Can place tile, then item, then remove item = tile remains
- [x] Grid controls moved to left panel
- [x] Grid displays in full width
- [x] Save/load preserves layer order (tiles first)
- [x] Click-to-place works with tile overlay system
- [x] Drag-and-drop works with tile overlay system

## Files Modified

1. **index.html**:
   - Moved grid controls to left panel HTML
   - Updated `canPlaceItem()` with itemType parameter
   - Updated `clearGridArea()` with keepTiles parameter
   - Updated `placeItem()` to handle transparency
   - Updated `loadGrid()` to sort items by layer
   - Updated all placement checks (handleGridClick, handleDragOver, etc.)

2. **style.css**:
   - Added `.grid-controls-panel` styling
   - Added `.btn-block` for full-width buttons
   - Changed `.placed-item` background to transparent
   - Added shadows to `.placed-item-icon-only`
   - Updated `.placed-item:hover` with filter instead of box-shadow

## Future Enhancements

- [ ] Layer visibility toggles (show/hide tiles, show/hide items)
- [ ] Multi-select for bulk tile placement
- [ ] Pattern fill tool (paint multiple tiles at once)
- [ ] Copy/paste tile patterns
- [ ] Tile preview on hover (see tile color before placing)
- [ ] Item rotation on tiles
- [ ] Different icon sizes based on zoom level
- [ ] Snap to grid enhancement for better alignment
