var config = {container:"#tree-root",nodeAlign:"BOTTOM",connectors:{type:"step"},node:{HTMLclass:"nodeExample1"},hideRootNode:true},
root = {},
category600020 = {text:{name:"Cat\u00e9gorie",title:"Substances organiques"},HTMLclass:"light-gray",parent:root},
group660421 = {text:{name:"Groupe",title:"Liquides et vapeurs organiques"},HTMLclass:"light-gray",parent:category600020},
family460003 = {text:{name:"Famille",title:"Solvants organiques",contact:460003},HTMLclass:"blue",parent:group660421},
subFamily530199 = {text:{name:"SousFamille",title:"Hydrocarbures aromatiques mononucl\u00e9aires",contact:530199},HTMLclass:"blue",parent:family460003},
agent430104 = {text:{name:"Agent",title:"Styr\u00e8ne"},HTMLclass:"blue related-agent",parent:subFamily530199},
agent430103 = {text:{name:"Agent",title:"Xyl\u00e8ne"},HTMLclass:"blue related-agent",parent:subFamily530199},
agent430102 = {text:{name:"Agent",title:"Tolu\u00e8ne",contact:430102},HTMLclass:"blue selected-agent",parent:subFamily530199},
agent430101 = {text:{name:"Agent",title:"Benz\u00e8ne",contact:430101},HTMLclass:"blue selected-agent",parent:subFamily530199},
chart_config = [config, root, category600020, group660421, family460003, subFamily530199, agent430104, agent430103, agent430102, agent430101];