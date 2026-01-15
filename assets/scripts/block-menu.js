console.log("Menu block editor script loaded.");
((blocks,element,blockEditor,data)=>{
const { __ }=window.wp.i18n;
const {registerBlockType}=blocks;
const el=element.createElement;
const useBlockProps=blockEditor.useBlockProps;
const InnerBlocks=blockEditor.InnerBlocks;
const useInnerBlocksProps=blockEditor.useInnerBlocksProps;
const useSelect=data.useSelect;
registerBlockType(
"aurise/menu",
{
apiVersion: 2,
title:"Menu",
icon:"dashicons-menu-alt3",
description:"Display website menu information.",
category:"aurise",
keywords:["aurise","orise","arise","tessa","menu","navigation","link","url"],

// Block Attributes
attributes:{
    "menu": {
        "type": "string",
        "default": ""
    },
    "menu_class": {
        "type": "string",
        "default": ""
    },
    "depth": {
        "type": "string",
        "default": "0"
    }
},

// Examples Configuration
example:{attributes:{
    "menu_class": "au-menu-footer",
    "depth": "2"
}},

// Edit Update Functions
edit:function(props,setAttributes,className){
function update_menu(event){props.setAttributes({menu:event.target.value})}
function update_menu_class(event){props.setAttributes({menu_class:event.target.value})}
function update_depth(event){props.setAttributes({depth:event.target.value})}

// START Block Editor
let blockProps=useBlockProps(),
innerBlockProps=useInnerBlocksProps(blockProps),
output=el("div",
blockProps,
el("div",
{className:"au-block-editor au_menu"},
el("h3",null,"Menu"),
el("div",{ className: "au-row" },

// START Block Editor Fields

el(
                            "label",{className:"au-input-field col-xs-12 col-md-6"},
                            el("span",{className: "au-input-label"},"Menu ID"),
                            el(
                                "select",
                                {
                                    value: props.attributes.menu,
                                    onChange: update_menu
                                },
                                
el("option",{value:"3"},"Main Menu"),
el("option",{value:"24"},"Some Multilevel Menu"),
el("option",{value:"47"},"Spam Fighter Testing")

                            )
) // Close form element
,

el(
                            "label",{className:"au-input-field col-xs-12 col-md-12"},
                            el("span",{className: "au-input-label"},"Additional classes to add to the menu wrapper."),
                            el("input", {
                                type: "text",
                                value: props.attributes.menu_class,
                                onChange: update_menu_class
                            })
                       ) // Close form element
,

el(
                            "label",{className:"au-input-field col-xs-12 col-md-6"},
                            el("span",{className: "au-input-label"},"Maximum menu depth"),
                            el("input", {
                                type: "number",
                                value: props.attributes.depth,
                                onChange: update_depth,min:"0",step:"1"
                            })
                       ) // Close form element

// END Block Editor Fields

)// Close row element
)// Close wrapping element
);// Close outer element element and variables
return output;},
// END Block Editor



// Save Block
save:function(props){
return InnerBlocks.Content;
}

}
);
})
(window.wp.blocks,window.wp.element,window.wp.blockEditor,window.wp.data);