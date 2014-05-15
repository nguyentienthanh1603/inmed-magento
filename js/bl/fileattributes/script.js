/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   BL
 * @package    BL_FileAttributes
 * @copyright  Copyright (c) 2011 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

if (typeof(blfa) == 'undefined') {
    var blfa = {};
}

blfa.SystemConfigArrayFieldCompacter = Class.create()
blfa.SystemConfigArrayFieldCompacter.prototype =
{
    initialize: function(containerId, compactedWidth, config)
    {
        // Some cells styles are not working with IE 6/7, then do nothing at all on those browsers 
        this.isAppliable = (/MSIE (\d+\.\d+);/.test(navigator.userAgent));
        this.isAppliable = (this.isAppliable ? (Number(RegExp.$1) != 6) && (Number(Regexp.$1) != 7) : true);
        
        this.containerId = containerId;
        this.container   = $(this.containerId);
        this.table = this.container.getElementsBySelector('table').first();
        
        this.compactedWidth = compactedWidth;
        this.config = Object.extend({
            compactImageUrl: '',
            unpackImageUrl: '',
            compactTitle: '',
            unpackTitle: ''
        }, config || {});
        
        this.unpackedColumn = null;
        this.initRows();
        this.initColumnsWidths(true, true);
    },
    
    initRows: function()
    {
        if (!this.isAppliable) {
            return;
        }
        
        this.rows = this.table.getElementsBySelector('tr');
        this.rowsNumber = this.rows.length;
        this.rowsCells  = $H({});
        this.columnsNumber = 0;
        this.columnsTitles = $A({});
        
        this.rows.each(function(row, rowIndex){
            if (rowIndex < this.rowsNumber-1) {
                // All rows but last ("Add row") one
                var cellTagName = (rowIndex == 0 ? 'th' : 'td');
                var cells = $(row).getElementsBySelector(cellTagName);
                
                if (rowIndex == 0) {
                    this.columnsNumber = cells.length;
                }
                
                cells.each(function(cell, columnIndex){
                    cell = $(cell);
                    
                    if (columnIndex < this.columnsNumber-1) {
                        // All cells but last one (actions buttons)
                        if (rowIndex == 0) {
                            // Header row
                            
                            // Get column title (will be used for all column's cells)
                            this.columnsTitles.push(cell.innerHTML);
                            cell.writeAttribute('title', cell.innerHTML);
                            
                            // Add a button to shrink / extends the whold corresponding column
                            var img = $(document.createElement('img'));
                            img.writeAttribute({
                                'src': this.config.unpackImageUrl,
                                'title': this.config.unpackTitle,
                                'alt': this.config.unpackTitle
                            });
                            img.setStyle({
                                'cursor': 'pointer',
                                'verticalAlign': 'middle',
                                'marginRight': '3px'
                            });
                            
                            img.observe('click', function(){
                                if (this.unpackedColumn
                                    && (this.unpackedColumn-1 == columnIndex)) {
                                    this.compactColumn(columnIndex);
                                    this.unpackedColumn = null;
                                } else {
                                    if (this.unpackedColumn) {
                                        this.compactColumn(this.unpackedColumn-1);
                                    }
                                    this.unpackColumn(columnIndex);
                                    this.unpackedColumn = columnIndex+1;
                                }
                            }.bind(this));
                            
                            cell.insert({'top': img});
                        } else {
                            // Common row
                            cell.writeAttribute('title', this.columnsTitles[columnIndex]);
                        }
                    }
                }.bind(this));
                
                this.rowsCells.set(row.id, cells);
            }
        }.bind(this));
    },
    
    setCellWidth: function(cell, width, unpacked)
    {
        cell.setStyle({
            'width': width+'px',
            'maxWidth': (unpacked ? '' : width+'px'),
            'overflow': (unpacked ? 'visible' : 'hidden')
        }).writeAttribute('width', width);
    },
    
    setColumnWidth: function(index, width, unpacked)
    {
        this.rowsCells.values().each(function(cells){
            this.setCellWidth(cells[index], width, unpacked);
        }.bind(this));
    },
    
    initColumnsWidths: function(all, option)
    {
        if (!this.isAppliable) {
            return;
        }
        
        this.table.setStyle({'tableLayout': 'auto'});
        
        if (!all) {
            // Row given : init its widths and refresh columns widths whenever needed
            var rowId = option;
            this.rowsWidths.set(rowId, $A({}));
            var updatedColumns = $A({});
            
            this.rowsCells.get(rowId).each(function(cell, columnIndex){
                var width = cell.getWidth();
                this.rowsWidths.get(rowId)[columnIndex] = width; 
                if (width > this.columnsWidths[columnIndex]) {
                    this.columnsWidths[columnIndex] = width;
                    updatedColumns.push(columnIndex);
                } 
            }.bind(this));
            
            for (var i=0; i<this.columnsNumber; i++) {
                if (updatedColumns.indexOf(i) != -1) {
                    if (i == this.columnsNumber-1) {
                        // Last column was updated : fix again its dimensions accordingly
                        this.setColumnWidth(i, this.columnsWidths[i], true);
                    } else if (this.unpackedColumn && (i == this.unpackedColumn-1)) {
                        // Currently unpacked column was updated : unpack it again
                        this.unpackColumn(i);
                    } else {
                        // All other columns : compact current cell 
                        this.setColumnWidth(i, this.compactedWidth, false);
                    }
                } else {
                    if (this.unpackedColumn && (this.unpackedColumn-1 == i)) {
                        this.setCellWidth(this.rowsCells.get(rowId)[i], this.columnsWidths[i], true);
                    } else {
                        this.setCellWidth(this.rowsCells.get(rowId)[i], this.compactedWidth, false);
                    }
                }
            }
        } else {
            if (option) {
                // Complete init of columns/rows cells widths
                this.columnsWidths = $A({});
                this.rowsWidths = $H({});
                
                for (var i=0; i<this.columnsNumber; i++) {
                    this.columnsWidths[i] = 0;
                }
                
                // Compute max width for each column
                this.rowsCells.values().each(function(cells, rowIndex){
                    var rowId = this.rows[rowIndex].id;
                    this.rowsWidths.set(rowId, $A({}));
                    
                    for (var i=0; i<this.columnsNumber; i++) {
                        this.rowsWidths.get(rowId)[i] = cells[i].getWidth();
                        this.columnsWidths[i] = Math.max(this.rowsWidths.get(rowId)[i], this.columnsWidths[i]);
                    }
                }.bind(this));
                
                // Fix last column width now, as it is not said to be compactable
                this.setColumnWidth(this.columnsNumber-1, this.columnsWidths[this.columnsNumber-1], true);
                
                // Then compact all columns but action one
                for (var i=0; i<this.columnsNumber-1; i++) {
                    this.compactColumn(i);
                }
            } else {
                // Only refresh columns widths from already computed rows cells widths
                var newWidths = $A({});
                for (var i=0; i<this.columnsNumber; i++) {
                    newWidths[i] = 0;
                }
                
                this.rowsWidths.values().each(function(widths){
                    for (var i=0; i<this.columnsNumber; i++) {
                        newWidths[i] = Math.max(widths[i], newWidths[i]);
                    }
                }.bind(this));
                
                newWidths.each(function(width, index){
                    if (width != this.columnsWidths[index]) {
                        // Changed width
                        this.columnsWidths[index] = width;
                        
                        if (index == this.columnsNumber-1) {
                            // Last column was updated : fix again its dimensions accordingly
                            this.setColumnWidth(this.columnsNumber-1, width, true);                    
                        } else if (this.unpackedColumn && (index == this.unpackedColumn-1)) {
                            // Currently unpacked column was updated : unpack it again
                            this.unpackColumn(index);
                        }
                    }
                }.bind(this));
            }
        }
        
        this.table.setStyle({'tableLayout': 'fixed'});
    },
    
    unpackColumn: function(index)
    {
        if (!this.isAppliable) {
            return;
        }
        
        this.rowsCells.values().each(function(cells, rowIndex){
            var cell = cells[index];
            if (cell) {
                this.setCellWidth(cell, this.columnsWidths[index], true);
                if (rowIndex == 0) {
                    cell.getElementsBySelector('img').first().writeAttribute({
                        'src': this.config.compactImageUrl,
                        'title': this.config.compactTitle,
                        'alt': this.config.compactTitle
                    });
                }
            }
        }.bind(this));
    },
    
    compactColumn: function(index)
    {
        if (!this.isAppliable) {
            return;
        }
        
        this.rowsCells.values().each(function(cells, rowIndex){
            var cell = cells[index];
            if (cell) {
                this.setCellWidth(cell, this.compactedWidth, false);
                if (rowIndex == 0) {
                    cell.getElementsBySelector('img').first().writeAttribute({
                        'src': this.config.unpackImageUrl,
                        'title': this.config.unpackTitle,
                        'alt': this.config.unpackTitle
                    });
                } 
            }
        }.bind(this));
    },
    
    addRow: function(row)
    {
        if (!this.isAppliable) {
            return;
        }
        
        // Init row values
        var rowId = row.id;
        this.rowsCells.set(rowId, $(row).getElementsBySelector('td'));
        this.rowsCells.get(rowId).each(function(cell, columnIndex){
            cell = $(cell);
            if (columnIndex < this.columnsNumber-1) {
                cell.writeAttribute('title', this.columnsTitles[columnIndex]);
            }
        }.bind(this));
        
        // Refresh widths
        this.initColumnsWidths(false, rowId);
    },
    
    removeRow: function(row)
    {
        if (!this.isAppliable) {
            return;
        }
        
        // Delete rows widths and cells
        var rowId = row.id;
        this.rowsCells.unset(rowId);
        this.rowsWidths.unset(rowId);
        // Refresh widths
        this.initColumnsWidths(true, false);
    }
}