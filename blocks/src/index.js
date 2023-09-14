const { registerBlockType } = wp.blocks;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, ToggleControl, TextControl, SelectControl } = wp.components;

registerBlockType('wpchill-carmanagement/car-filter', {
    title: 'Car Filter',
    icon: 'car',
    category: 'common',
    attributes: {
        showFilter: {
            type: 'boolean',
            default: true
        },
        filterTitle: {
            type: 'string',
            default: 'Car Filter'
        },
        buttonLabel: {
            type: 'string',
            default: 'Filter'
        },
        filterStyle: {
            type: 'string',
            default: 'default'
        }
    },
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const { showFilter, filterTitle, buttonLabel, filterStyle } = attributes;

        return (
            <div>
                <InspectorControls>
                    <PanelBody title="Filter Settings">
                        <ToggleControl
                            label="Show Filter"
                            checked={showFilter}
                            onChange={() => setAttributes({ showFilter: !showFilter })}
                        />
                        <TextControl
                            label="Filter Title"
                            value={filterTitle}
                            onChange={(value) => setAttributes({ filterTitle: value })}
                        />
                        <TextControl
                            label="Button Label"
                            value={buttonLabel}
                            onChange={(value) => setAttributes({ buttonLabel: value })}
                        />
                        <SelectControl
                            label="Filter Style"
                            value={filterStyle}
                            options={[
                                { label: 'Default', value: 'default' },
                                { label: 'Compact', value: 'compact' },
                                // Add more styles as needed
                            ]}
                            onChange={(value) => setAttributes({ filterStyle: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                {"Car Filter Form will appear here."}
            </div>
        );
    },
    save: function() {
        return null; // The save function should return null because the block will be rendered with PHP on the server-side.
    }
});
