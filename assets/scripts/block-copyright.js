console.log("Copyright block editor script loaded.");
((blocks,element,blockEditor,data)=>{
const { __ }=window.wp.i18n;
const {registerBlockType}=blocks;
const el=element.createElement;
const useBlockProps=blockEditor.useBlockProps;
const InnerBlocks=blockEditor.InnerBlocks;
const useInnerBlocksProps=blockEditor.useInnerBlocksProps;
const useSelect=data.useSelect;
registerBlockType(
"aurise/copyright",
{
apiVersion: 2,
title:"Copyright",
icon:"businesswoman",
description:"Display website copyright information.",
category:"aurise",
keywords:["aurise","orise","arise","tessa","copyright","author","year","site"],

// Block Attributes
attributes:{
    "menu_id": {
        "type": "string",
        "default": ""
    },
    "public_login": {
        "type": "string",
        "default": ""
    }
},

// Examples Configuration
example:{attributes:{
    "public_login": "on"
}},

// Edit Update Functions
edit:function(props,setAttributes,className){
function update_menu_id(event){props.setAttributes({menu_id:event.target.value})}
function update_public_login(event){if(event.target.checked){props.setAttributes({public_login:"on"})}else{props.setAttributes({public_login:""})}}

// START Block Editor
let blockProps=useBlockProps(),
innerBlockProps=useInnerBlocksProps(blockProps),
output=el("div",
blockProps,
el("div",
{className:"au-block-editor au_copyright"},
el("h3",null,"Copyright"),
el("div",{ className: "au-row" },

// START Block Editor Fields

el(
                            "label",{className:"au-input-field col-xs-12 col-md-6"},
                            el("span",{className: "au-input-label"},"Menu ID (int)"),
                            el(
                                "select",
                                {
                                    value: props.attributes.menu_id,
                                    onChange: update_menu_id
                                },
                                
el("option",{value:"3"},"Main Menu"),
el("option",{value:"24"},"Some Multilevel Menu"),
el("option",{value:"47"},"Spam Fighter Testing")

                            )
) // Close form element
,

el(
                           "label",{className:"au-input-field col-xs-12 col-md-6"},
                            el("input", {
                                type: "checkbox",
                                value: "public_login",
                                checked: props.attributes.public_login ? "checked" : "",
                                onChange: update_public_login
                            }),
                            el("span",{className: "au-input-label"},"Include User Account Links in Menu"),
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