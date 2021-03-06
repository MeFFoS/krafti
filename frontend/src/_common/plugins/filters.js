import Vue from 'vue'
import smartTruncate from 'smart-truncate'
import {noun, verb} from 'plural-ru'

export default ({app}, inject) => {
  inject('password', (length) => {
    if (!length) {
      length = 12
    }
    const charset = 'abcdefghijklnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
    let password = ''

    for (let i = 0, n = charset.length; i < length; ++i) {
      password += charset.charAt(Math.floor(Math.random() * n))
    }

    return password
  })

  Vue.filter('price', (price, discount = 0) => {
    if (!discount) {
      return price
    }
    if (typeof discount === 'object') {
      if (discount.discount) {
        discount = discount.discount
      } else {
        return price
      }
    }
    if (typeof discount === 'string') {
      if (discount[discount.length - 1] === '%') {
        discount = price * (discount.slice(0, -1) / 100)
      }
    }
    if (discount > 0) {
      price -= discount
    }
    return price
  })

  Vue.filter('number', (value) => {
    return value ? value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') : 0
  })

  Vue.filter('noun', (num, forms) => {
    const tmp = forms.split('|')

    return noun(num, tmp[0], tmp[1], tmp[2])
  })

  Vue.filter('verb', (num, forms) => {
    const tmp = forms.split('|')

    return verb(num, tmp[0], tmp[1], tmp[2])
  })

  Vue.filter('fullname', (value) => {
    if (!value) {
      return value
    }

    return value
      .split(/\s+/)
      .map((item) => Vue.filter('ucfirst')(item))
      .join(' ')
  })

  Vue.filter('truncate', (value, chars = 30, options = {}) => {
    return smartTruncate(value.replace(/<.*?>/g, ''), chars, options)
  })

  Vue.filter('date', (value, format = 'DD.MM.YYYY') => {
    return app.$moment(value, 'YYYY-MM-DDTHH:mm:ssZ').format(format)
  })

  Vue.filter('datetime', (value) => {
    return app.$moment(value, 'YYYY-MM-DDTHH:mm:ssZ').format('DD.MM.YYYY HH:mm')
  })

  Vue.filter('dateago', (value) => {
    return app.$moment(value, 'YYYY-MM-DDTHH:mm:ssZ').fromNow()
  })

  Vue.filter('years', (value) => {
    return app.$moment().diff(app.$moment(value, 'YYYY-MM-DD'), 'years')
  })

  Vue.filter('phone', (value) => {
    return value.replace(/^(\d{3})(\d{3})(\d{4})$/g, '$1 $2 $3')
  })

  Vue.filter('ucfirst', (value) => {
    if (value.length) {
      value = value.charAt(0).toUpperCase() + value.toLowerCase().slice(1)
    }
    return value
  })

  Vue.filter('duration', (value) => {
    const duration = app.$moment.duration(value, 'seconds')

    return app.$moment.utc(duration.asMilliseconds()).format('HH:mm:ss')
  })
}
