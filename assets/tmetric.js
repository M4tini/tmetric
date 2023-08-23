function modifyDate (name, addDays = 1) {
  const dateFrom = document.getElementsByName(name)[0]
  const date = new Date(dateFrom.value)

  date.setDate(date.getDate() + addDays)

  dateFrom.value = dateToString(date)
}

function targetMonth (year, monthNumber) {
  const date = new Date(year, monthNumber - 1, 1)

  const dateFrom = document.getElementsByName('date_from')[0]
  dateFrom.value = dateToString(date)

  date.setMonth(monthNumber)
  date.setDate(0)

  const dateTo = document.getElementsByName('date_to')[0]
  dateTo.value = dateToString(date)
}

function dateToString (date) {
  return [
    date.getFullYear(),
    ('0' + (date.getMonth() + 1)).slice(-2),
    ('0' + (date.getDate())).slice(-2),
  ].join('-')
}
