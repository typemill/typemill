app.config.globalProperties.$filters = {
  translate(value)
  {
	  if (!value) return ''
	  translation_key = value.replace(/[ ]/g,"_").replace(/[.]/g, "_").replace(/[,]/g, "_").replace(/[-]/g, "_").replace(/[,]/g,"_").toUpperCase()
	  translation_value = data.labels[translation_key]
	  if(!translation_value || translation_value.length === 0){
	    return value
	  } else {
	    return data.labels[translation_key]
	  }
  }
}
