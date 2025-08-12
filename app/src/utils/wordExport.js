import {
    Document,
    Packer,
    Table,
    TableRow,
    TableCell,
    Paragraph,
    Bookmark,
    TextRun,
    WidthType,
    VerticalAlign,
    HeightRule,
    AlignmentType,
    ShadingType,
    TableLayoutType
} from 'docx';

/**
 * Cria um array de TextRun com quebras de linha a partir de uma string com '\n'.
 * @param {string} text - O texto a ser processado.
 * @returns {TextRun[]} - Um array de objetos TextRun.
 */
function createMultiLineText(text) {
    const lines = text.split('\n');
    // Usa reduce para construir o array, adicionando um objeto de quebra de linha antes de cada nova linha
    return lines.reduce((acc, line, index) => {
        if (index > 0) {
            acc.push(new TextRun({ break: 1 }));
        }
        acc.push(new TextRun(line));
        return acc;
    }, []);
}


export function exportStructureWord(structure, layoutTabela) {
    const tableRows = [];
    const rowHeight = 302;

    layoutTabela.forEach((row) => {
        if (row.type === 'header') {
            tableRows.push(
                new TableRow({
                    height: { value: rowHeight, rule: HeightRule.ATLEAST },
                    children: [
                        new TableCell({
                            children: [new Paragraph({ text: row.label, style: 'headerPara' })],
                            columnSpan: 2,
                            verticalAlign: VerticalAlign.CENTER,
                            shading: { type: ShadingType.SOLID, fill: "9C8A87", color: "9C8A87" },
                        }),
                        new TableCell({
                            children: [new Paragraph({ text: 'Rastreabilidade', style: 'headerPara' })],
                            verticalAlign: VerticalAlign.CENTER,
                            shading: { type: ShadingType.SOLID, fill: "9C8A87", color: "9C8A87" },
                        }),
                    ],
                })
            );
        } else if (row.type === 'data') {
            const valor = structure[row.key] != null ? String(structure[row.key]) : '';
            const bookmarkName = row.key;
            
            const cells = [
                // Célula 1: Nome do Campo
                new TableCell({
                    children: [new Paragraph({ children: createMultiLineText(row.label), style: 'normalPara' })],
                    verticalAlign: VerticalAlign.CENTER,
                }),
                // Célula 2: Valor do Campo
                new TableCell({
                    children: [
                        new Paragraph({
                            style: 'normalPara',
                            children: [
                                new Bookmark({
                                    id: bookmarkName,
                                    name: bookmarkName,
                                    children: [new TextRun(valor)],
                                }),
                            ],
                        }),
                    ],
                    verticalAlign: VerticalAlign.CENTER,
                }),
            ];
            
            if (row.rastreabilidade) {
                const rastreabilidadeCellOptions = {
                    // Célula de Rastreabilidade 
                    children: [new Paragraph({ children: createMultiLineText(row.rastreabilidade.text || ''), style: 'normalPara' })],
                    verticalAlign: VerticalAlign.CENTER,
                };
                
                if (row.rastreabilidade.rowSpan) {
                    rastreabilidadeCellOptions.rowSpan = row.rastreabilidade.rowSpan;
                }
                
                cells.push(new TableCell(rastreabilidadeCellOptions));
            }

            tableRows.push(
                new TableRow({
                    height: { value: rowHeight, rule: HeightRule.ATLEAST },
                    children: cells,
                })
            );
        }
    });

    const table = new Table({
        rows: tableRows,
        columnWidths: [2971, 4394, 2546], 
        layout: TableLayoutType.FIXED,
    });

    const doc = new Document({
        styles: {
            default: {
                document: { run: { font: "Arial" } },
            },
            paragraphStyles: [
                {
                    id: 'normalPara',
                    name: 'Normal Para',
                    run: { size: 20, color: "000000" },
                    paragraph: { alignment: AlignmentType.LEFT },
                },
                {
                    id: 'headerPara',
                    name: 'Header Para',
                    run: { size: 24, bold: true, color: "FFFFFF" },
                    paragraph: { alignment: AlignmentType.CENTER },
                },
                {
                    id: 'tableTitle',
                    name: 'Table Title',
                    run: { font: "Arial", size: 20, bold: false },
                    paragraph: { alignment: AlignmentType.CENTER },
                },
            ],
        },
        sections: [{
            children: [
                new Paragraph({ text: 'Tabela 4.1 - Características técnicas da estrutura', style: 'tableTitle' }),
                new Paragraph({ text: '' }),
                table
            ]
        }],
    });

    Packer.toBlob(doc).then(blob => {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Características técnicas da estrutura ${structure.finalidade || 'null'}.docx`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });
}