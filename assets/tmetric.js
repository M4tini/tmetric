function modifyDate (name, addDays = 1) {
  const dateFrom = document.getElementsByName(name)[0]
  const date = new Date(dateFrom.value)
  date.setDate(date.getDate() + addDays)

  dateFrom.value = [
    date.getFullYear(),
    ('0' + (date.getMonth() + 1)).substr(-2),
    ('0' + (date.getDate())).substr(-2)
  ].join('-')
}
