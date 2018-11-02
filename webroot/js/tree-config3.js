var config = {container:"#tree-root",nodeAlign:"BOTTOM",connectors:{type:"step"},node:{HTMLclass:"nodeExample1"}},
category600020 = {text:{name:"Cat\u00e9gorie",title:"Substances organiques"},HTMLclass:"light-gray"},
group660421 = {text:{name:"Groupe",title:"Liquides et vapeurs organiques"},HTMLclass:"light-gray",parent:category600020},
family460003 = {text:{name:"Famille",title:"Solvants organiques"},HTMLclass:"light-gray",parent:group660421},
subFamily530199 = {text:{name:"SousFamille",title:"Hydrocarbures aromatiques mononucl\u00e9aires"},HTMLclass:"light-gray",parent:family460003},
agent430103 = {text:{name:"Agent",title:"Xyl\u00e8ne"},HTMLclass:"light-gray",parent:subFamily530199},
agent430102 = {text:{name:"Agent",title:"Tolu\u00e8ne"},HTMLclass:"light-gray",parent:subFamily530199},
chart_config = [config, category600020, group660421, family460003, subFamily530199, agent430103, agent430102];