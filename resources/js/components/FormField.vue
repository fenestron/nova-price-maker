<template lang="pug">
#{"default-field"}(:field="field" :errors="errors")
    template(slot="field")
        .price-component-wrapper
            .price-component
                .inputs
                    .inputs-row
                        .input-item
                            label Число участников
                            .inputs-item-fields
                                input.number-input(v-model="inputs.min_clients" type="number" placeholder="Минимум" @change="validateMinClients")
                                input.number-input(v-model="inputs.max_clients" type="number" placeholder="Максимум" @change="validateMaxClients")

                        .input-item
                            label Число активностей
                            input(v-model="inputs.asset_count" type="number" @change="validateAssetCount")

                        .input-item
                            label Тип оплаты
                            select(v-model="inputs.client_type")
                                option(v-for="type in paymentTypes" :value="type.techname") {{ type.name }}

                        .input-item(v-if="inputs.client_type === 'team'")
                            label Тип цены

                            select(v-model="inputs.price_type")
                                option(v-for="type in priceTypes" :value="type.techname") {{ type.name }}

                        .input-item(v-if="inputs.client_type && inputs.client_type === 'team' && inputs.price_type === 'fix_client'")
                            label Фиксированная цена
                            input.number-input(v-model="inputs.fix_price" type="number")

                    .row-title Продолжительности
                    .inputs-row
                        .input-item(
                            v-for="(duration, index) in inputs.durations"
                            :key="index"
                            :class="{'input-item-invalid': inputErrors.durationErrors[duration.id] && inputErrors.durationErrors[duration.id].length}"
                            )
                            .input-item-header
                                label Продолжительность {{index + 1}}
                                .remove-button(v-if="inputs.durations.length > 1" @click="removeDuration(duration)") x
                            .inputs-item-fields
                                input(v-model="duration.name" placeholder="Название" @input="validateDurationName(duration)")
                                input.number-input(v-model="duration.duration" type="number" @change="validateDuration(duration)" placeholder="Длительность (мин.)")
                            .error-text(v-if="inputErrors.durationErrors[duration.id].length" v-for="error in inputErrors.durationErrors[duration.id]") {{ error }}


                        .add-button(@click="addDuration") +

                    .row-title Опции
                    .inputs-row
                        .input-item(v-for="(option, index) in inputs.options" :key="index" :class="{'input-item-invalid': inputErrors.optionErrors[option.id] && inputErrors.optionErrors[option.id].length}")
                            .input-item-header
                                label Опция {{ index + 1 }}
                                .remove-button(v-if="inputs.options.length > 1" @click="removeOption(option)") x
                            .inputs-item-fields
                                input(v-model="option.name" placeholder="Название опции" @input="validateOption(option)")
                            .error-text(v-if="inputErrors.optionErrors[option.id].length" v-for="error in inputErrors.optionErrors[option.id]") {{ error }}

                        .add-button(@click="addOption") +

                    .row-title Рабочие зоны
                    .inputs-row
                        .input-item
                            label Тип генерации рабочих зон
                            select(v-model="inputs.time_type" @change="changeWorkzonesType")
                                option(v-for="type in workzonesTypes" :value="type.techname") {{ type.name }}

                    .inputs-row
                        .input-item(v-for="(workzone, index) in inputs.workzones" :key="index")
                            .input-item-header
                                label Рабочая зона {{ index + 1 }}
                                .remove-button(v-if="inputs.workzones.length > 1" @click="removeWorkzone(workzone)") x
                            .inputs-item-fields
                                .workzone-days
                                    .workzone-day(v-for="day in workzone.weekdays") {{ day.name || days.find(item => item.techname === day).name }}
                                .workzone-hours(style="margin-bottom: 10px")
                                    span(v-if="inputs.time_type" v-for="(time, i) in workzone.time")
                                        span(v-if="inputs.time_type === 'auto'") {{ time.from }} - {{ time.to }}
                                        span(v-else-if="inputs.time_type === 'manual'") {{ time.t }}
                                        span(v-if="workzone.time.length > 1 && i + 1 != workzone.time.length") ,

                        .input-item(v-if="showNewWorkzoneForm && inputs.time_type")
                            label Добавить рабочую зону
                            .inputs-item-fields
                                .new-workzone-hours(v-for="(time, index) in newWorkzone.time" :key="time.id")
                                    template(v-if="inputs.time_type === 'auto'")
                                        input(v-model="newWorkzone.time[index].from" placeholder="От" v-mask="'##:##'")
                                        input(v-model="newWorkzone.time[index].to" placeholder="До" v-mask="'##:##'")
                                    template(v-else-if="inputs.time_type === 'manual'")
                                        input(v-model="newWorkzone.time[index].t" placeholder="Время" v-mask="'##:##'")

                                    button.new-time-button(v-if="index === 0" @click.prevent="addTimeInNewWorkzone()") +
                                    button.new-time-button(v-else @click.prevent="removeTimeFromWorkzone(index)") -

                                .new-workzone-days
                                    multiselect.days-input(
                                        :options="days"
                                        v-model="newWorkzone.weekdays"
                                        track-by="name"
                                        label="name"
                                        :close-on-select="false"
                                        :multiple="true"
                                        :searchable="false"
                                    )

                                //- Здесь будут ошибки валидации воркзоны
                                //- .error-text Ошибочки

                                button(@click.prevent="addWorkzone") Добавить
                                button(@click.prevent="showNewWorkzoneForm = false") Отмена
                        .add-button(@click="showNewWorkzoneForm = true" v-if="!showNewWorkzoneForm && inputs.time_type") +


                .row-title Таблица цен
                .tabs(v-if="isTableReady && !inputErrors.tableErrors.length")
                    .tab(v-for="(tab, index) in inputs.durations" :key="index" v-if="tab.name" @click="selectedDuration = tab.id" :class="{ 'selected-tab': tab.id == selectedDuration }") {{ tab.name }}
                        span(v-if="tab.duration") , {{ tab.duration }}

                .table(v-if="isTableReady && !inputErrors.tableErrors.length")
                    .table-content-left
                        .empty
                        .member-item(v-if="inputs.client_type === 'various'" v-for="(member, index) in (inputs.max_clients - inputs.min_clients + 1)")
                            .members-count {{ pluralizeMemberWord(Number(index) + Number(inputs.min_clients)) }}
                            .workzones
                                .workzone(v-for="(workzone, index) in inputs.workzones" :key="index")
                                    .workzone-days
                                        .workzone-day(v-for="day in workzone.weekdays") {{ day.name || days.find(item => item.techname === day).name }}
                                    .workzone-hours(v-for="(time, i) in workzone.time")
                                        span(v-if="inputs.time_type === 'auto'") {{ time.from }} - {{ time.to }}
                                        span(v-else-if="inputs.time_type === 'manual'") {{ time.t }}
                                        span(v-if="workzone.time.length > 1 && i + 1 != workzone.time.length") ,

                        .member-item(v-if="inputs.client_type !== 'various'")
                            .members-count(v-if="inputs.client_type === 'team'") Оплата за группу ({{ `${inputs.min_clients}-${inputs.max_clients}` }})
                            .members-count(v-else-if="inputs.client_type === 'individual'") 1 участник
                            .workzones
                                .workzone(v-for="(workzone, index) in inputs.workzones" :key="index")
                                    .workzone-days
                                        .workzone-day(v-for="day in workzone.weekdays") {{ day.name || days.find(item => item.techname === day).name }}
                                    .workzone-hours(v-for="(time, i) in workzone.time")
                                        span(v-if="inputs.time_type === 'auto'") {{ time.from }} - {{ time.to }}
                                        span(v-else-if="inputs.time_type === 'manual'") {{ time.t }}
                                        span(v-if="workzone.time.length > 1 && i + 1 != workzone.time.length") ,


                    .table-content-right
                        .option-column(v-for="(option, index) in inputs.options" :key="index" v-if="option.name")
                            .option-name {{ option.name }}
                            //- Инпуты для типа оплаты "за клиента"
                            template(v-if="inputs.client_type === 'various'" v-for="(member, index) in (inputs.max_clients - inputs.min_clients + 1)")
                                .empty
                                .workzone-price
                                    input.number-input(
                                        v-for="workzone in inputs.workzones"
                                        type="number"
                                        placeholder="цена"
                                        v-model="table[selectedDuration][`client_${Number(index) + Number(inputs.min_clients)}`][`workzone_${workzone.id}`][option.id].price"
                                    )

                            //- Инпуты для остальных типов
                            template(v-if="inputs.client_type !== 'various'")
                                .empty
                                .workzone-price
                                    input.number-input(
                                        v-for="workzone in inputs.workzones"
                                        type="number"
                                        placeholder="цена"
                                        v-model="table[selectedDuration][inputs.client_type][`workzone_${workzone.id}`][option.id].price"
                                    )

                .table-errors(v-else-if="inputErrors.tableErrors.length")
                    h3 Ошибка построения таблицы
                    .table-error(v-for="error in inputErrors.tableErrors") {{ error }}
                .table-errors(v-else-if="!isTableReady") Таблица строится...
                //- button(@click="setFieldsForTable") Построить тобличку
                //- button(@click="fill") Подготовить данные
                //- button(@click="createWorkzonesArray") Подготовить воркзоны
                //- button(@click="logTable") Показать таблицу
</template>

<script>
  import {FormField, HandlesValidationErrors} from 'laravel-nova';
  import Multiselect from 'vue-multiselect'


  const WEEKDAYS = [
    {
      name: 'Пн',
      techname: 'monday'
    },
    {
      name: 'Вт',
      techname: 'tuesday'
    },
    {
      name: 'Ср',
      techname: 'wednesday'
    },
    {
      name: 'Чт',
      techname: 'thursday'
    },
    {
      name: 'Пт',
      techname: 'friday'
    },
    {
      name: 'Сб',
      techname: 'saturday'
    },
    {
      name: 'Вс',
      techname: 'sunday'
    },
  ]

  const DEFAULT_WEEKDAYS = [...WEEKDAYS.slice(0, 5)]

  export default {
    components: { Multiselect },

    mixins: [FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    data() {
      return {
        service_id: null,

        days: WEEKDAYS,
        paymentTypes: [
          {
            name: 'Индивидуальный',
            techname: 'individual'
          },
          {
            name: 'Группа участников',
            techname: 'team'
          },
          // {
          //     name: 'Зависит от числа участников',
          //     techname: 'various'
          // }
        ],
        priceTypes: [
          {
            name: 'За участника',
            techname: 'client'
          },
          {
            name: 'За команду',
            techname: 'team'
          },
          {
            name: 'Фикс + за участника',
            techname: 'fix_client'
          }
        ],
        workzonesTypes: [
          {
            name: 'Автоматический',
            techname: 'auto'
          },
          {
            name: 'Ручной',
            techname: 'manual'
          }
        ],
        showNewWorkzoneForm: false,
        newWorkzone: {},
        selectedDuration: 1,

        // Поля ввода
        inputs: {
          min_clients: 1,
          max_clients: 1,
          asset_count: 1,
          fix_price: 0,
          isPaymentTypeChanged: false,
          client_type: null,
          time_type: null,
          price_type: null,
          durations: [],
          options: [],
          workzones: [],
        },

        inputErrors: {
          optionErrors: {},
          durationErrors: {},
          newWorkzoneErrors: [],
          tableErrors: ['Заполнены не все поля']
        },

        isTableReady: false,
        table: {}
      }
    },
    mounted() {
      this.setInitialValues(this.field)
      this.setFieldsForTable(this.field)
    },
    watch: {
      'inputs': {
        handler() {
          this.isTableReady = false
          this.setFieldsForTable()
        },
        deep: true
      },
      'inputs.client_type' (previousValue, currentValue) {
        if (previousValue && currentValue) {
          if (previousValue !== currentValue) {
            return this.inputs.isPaymentTypeChanged = true
          }
        }
      },
    },
    methods: {
      fill(formData) {
        let data = {
          service_id: this.service_id,
          min_clients: this.inputs.min_clients,
          max_clients: this.inputs.max_clients,
          asset_count: this.inputs.asset_count,
          client_type: this.inputs.client_type,
          price_type: this.inputs.price_type,
          fix_price: this.inputs.fix_price,
          time_type: this.inputs.time_type,
          durations: this.inputs.durations,
          options: this.inputs.options,
          workzones: this.createWorkzonesArray(),
          costs: this.createCostsArray(),
        };

        formData.append(this.field.attribute, JSON.stringify(data) || '')
      },

      logTable() {
        console.log('This table', this.table)
      },

      setInitialValue() {
        this.setInitialValues(this.field);
        this.setFieldsForTable(this.field);
      },

      setInitialValues(data) {
        this.service_id = data && data.service_id || null
        this.inputs.min_clients = data && data.min_clients || 1
        this.inputs.max_clients = data && data.max_clients || 1
        this.inputs.fix_price = data && data.fix_price || 0
        this.inputs.asset_count = data && data.asset_count || 1
        this.inputs.client_type = data && data.client_type || 'individual'
        this.inputs.time_type = data && data.time_type || 'auto'
        this.inputs.price_type = data && data.price_type || 'client'

        this.inputs.durations = data && data.durations || [{
          id: 1,
          name: 'Продолжительность 1',
          duration: '90'
        }]

        this.selectedDuration = this.inputs.durations[0].id


        this.inputs.options = data && data.options || [{
          id: 1,
          name: 'Опция 1'
        }]

        this.inputs.workzones = data && data.workzones || [{
          id: 1,
          time: [{
            from: '08:00',
            to: '20:00'
          }],
          weekdays: DEFAULT_WEEKDAYS
        }]

        let defaultTime

        if (this.inputs.time_type === 'auto') {
          defaultTime = {
            id: 1,
            from: '',
            to: ''
          }
        } else {
          defaultTime = {
            id: 1,
            t: ''
          }
        }

        this.newWorkzone = {
          id: this.createUniqueId('workzones'),
          weekdays: [],
          time: [defaultTime]
        }

      },

      // Нужно подготовить реактивные свойства для полей цен
      setFieldsForTable(data) {
        this.isTableReady = false

        const errors = this.inputErrors.tableErrors = []

        if (!this.inputs.client_type) errors.push('Не заполнено поле "Тип оплаты"')
        if (this.inputs.client_type === 'team' && !this.inputs.price_type) errors.push('Не заполнено поле "Тип цены"')
        if (!this.inputs.time_type) errors.push('Не заполнено поле "Тип генерации рабочих зон"')

        if (errors.length) return

        // Строим таблицу циклами
        // Проходим по всем продолжительностям (durations)
        for (let d = 0; d < this.inputs.durations.length; d++) {
          let duration = this.table[this.inputs.durations[d].id]

          // // Для каждой продолжительности создаем объект, ключ которого - id продолжительности
          if (!duration || this.inputs.isPaymentTypeChanged) {
            duration = this.table[this.inputs.durations[d].id] = {}
          }

          // Блок кода для типа оплаты "зависит от числа участников"
          // Строятся поля для каждого клиента
          if (this.inputs.client_type === 'various') {
            // Этот объект будет содержать поле для каждого участника
            for (let m = this.inputs.min_clients; m <= this.inputs.max_clients; m++) {
              let client = duration[`client_${m}`]

              // создаем пустой объект для каждого участника
              if (!client) {
                client = duration[`client_${m}`] = {}
              }

              // создаем объект для каждой воркзоны
              for (let w = 0; w < this.inputs.workzones.length; w++) {
                let workzone = client[`workzone_${this.inputs.workzones[w].id}`]

                if (!workzone) {
                  workzone = client[`workzone_${this.inputs.workzones[w].id}`] = {}
                }

                // ...и объект для каждой опции
                for (let o = 0; o < this.inputs.options.length; o++) {
                  let option = workzone[this.inputs.options[o].id]

                  let price = 0

                  if (data && data.costs && data.costs.length) {
                    const priceObj = data.costs.find(cost => (
                            cost.duration_id == this.inputs.durations[d].id &&
                            cost.min_clients == m &&
                            cost.workzone_id == this.inputs.workzones[w].id &&
                            cost.option_id == this.inputs.options[o].id
                        )
                    )
                    if (priceObj) price = priceObj.cost
                  }

                  if (!option) {
                    workzone[this.inputs.options[o].id] = { price }
                  }
                }
              }
            }

            // Удаляем лишних клиентов
            for (let client in duration) {
              let clientNumber = client.split('_')[1]

              if (clientNumber > this.inputs.max_clients || clientNumber < this.inputs.min_clients) {
                delete duration[client]
              }
            }
          }

          // Если тип оплаты "индивидуальный" или "команда"
          else {
            let client = duration[this.inputs.client_type]

            if (!client) {
              client = duration[this.inputs.client_type] = {}
            }

            // создаем объект для каждой воркзоны
            for (let w = 0; w < this.inputs.workzones.length; w++) {
              let workzone = client[`workzone_${this.inputs.workzones[w].id}`]


              if (!workzone) {
                workzone = client[`workzone_${this.inputs.workzones[w].id}`] = {}
              }

              // ...и объект для каждой опции
              for (let o = 0; o < this.inputs.options.length; o++) {
                const option = workzone[this.inputs.options[o].id]

                let price = 0

                if (data && data.costs && data.costs.length) {
                  const priceObj = data.costs.find(cost => (
                          cost.duration_id == this.inputs.durations[d].id &&
                          cost.workzone_id == this.inputs.workzones[w].id &&
                          cost.option_id == this.inputs.options[o].id
                      )
                  )
                  if (priceObj) price = priceObj.cost
                }

                if (!option) {
                  workzone[this.inputs.options[o].id] = { price }
                }
              }
            }
          }

        }

        removeExtraWorkzones.apply(this)

        this.isTableReady = true
        this.inputs.isPaymentTypeChanged = false

        // Удалить лишние воркзоны
        function removeExtraWorkzones() {
          for (let duration in this.table) {
            if (this.inputs.client_type === 'various') {
              for (let client in this.table[duration]) {
                for (let workzone in this.table[duration][client]) {
                  removeWorkzone.apply(this, [duration, workzone, client])
                }
              }
            } else {
              let client = this.table[duration][this.inputs.client_type]

              for (let workzone in client) {
                removeWorkzone.apply(this, [duration, workzone])
              }
            }
          }
        }

        function removeWorkzone(duration, workzone, client) {
          let workzoneId = Number(workzone.split('_')[1])
          let existingWorkzones = this.inputs.workzones.map(zone => zone.id)

          if (!existingWorkzones.includes(workzoneId)) {
            if (client) {
              delete this.table[duration][client][workzone]
            } else {
              delete this.table[duration][this.inputs.client_type][workzone]
            }
          }
        }
      },

      addDuration() {
        let id = this.createUniqueId('durations')
        this.inputs.durations.push({ id, name: '', duration: null })
      },
      removeDuration(item) {
        const index = this.inputs.durations.indexOf(item)
        this.inputs.durations.splice(index, 1)

        if (this.selectedDuration === item.id) this.selectedDuration = this.inputs.durations[0].id

        delete this.table[item.id]
      },

      addOption() {
        let id = this.createUniqueId('options')
        this.inputs.options.push({ id, name: '' })
      },

      removeOption(item) {
        const index = this.inputs.options.indexOf(item)
        this.inputs.options.splice(index, 1)

        for (let duration in this.table) {
          for (let client in this.table[duration]) {
            for (let workzone in this.table[duration][client]) {
              // удалеям поле цены для удаляемой опции для каждой воркзоны. Oh, shi!
              delete this.table[duration][client][workzone][item.id]
            }
          }
        }
      },

      changeWorkzonesType(event) {
        const value = event.target.value

        const confirmation = confirm('Вы действительно хотите изменить тип генерации рабочих зон? Все настроенные рабочие зоны будут сброшены. Продолжить?')

        if (!confirmation) {
          if (value === 'auto') {
            this.inputs.time_type = 'manual'
          } else if (value === 'manual') {
            this.inputs.time_type = 'auto'
          }
        } else {
          if (value === 'auto') {
            this.inputs.workzones = [{
              id: 1,
              weekdays: DEFAULT_WEEKDAYS,
              time: [
                {
                  from: '10:00',
                  to: '20:00'
                }
              ]
            }]

            this.newWorkzone = {
              weekdays: [],
              time: [{
                from: '',
                to: ''
              }]
            }
          } else if (value === 'manual') {
            this.inputs.workzones = [{
              id: 1,
              weekdays: DEFAULT_WEEKDAYS,
              time: [{
                t: '10:00'
              }]
            }]

            this.newWorkzone = {
              weekdays: [],
              time: [{
                t: ''
              }]
            }
          }
        }
      },

      addWorkzone() {
        let id = this.createUniqueId('workzones')

        this.inputs.workzones.push({
          id,
          weekdays: this.newWorkzone.weekdays,
          time: this.newWorkzone.time
        })


        let newTimeObj = {}

        if (this.inputs.time_type === 'auto') {
          newTimeObj = {
            from: '',
            to: ''
          }
        } else if (this.inputs.time_type === 'manual') {
          newTimeObj = {
            t: ''
          }
        }

        this.newWorkzone.weekdays = []
        this.newWorkzone.time = [newTimeObj]
      },

      addTimeInNewWorkzone() {
        let newTime = {}

        if (this.inputs.time_type === 'auto') {
          newTime = {
            from: '',
            to: ''
          }
        } else if (this.inputs.time_type === 'manual') {
          newTime = {
            t: ''
          }
        }

        this.newWorkzone.time.push(newTime)
      },

      removeTimeFromWorkzone(index) {
        this.newWorkzone.time.splice(index, 1)
      },

      removeWorkzone(item) {
        const index = this.inputs.workzones.indexOf(item)
        this.inputs.workzones.splice(index, 1)
        for (let duration in this.table) {
          for (let client in this.table[duration]) {
            delete this.table[duration][client][`workzone_${item.id}`]
          }
        }

      },

      pluralizeMemberWord(number) {
        const titles = [`${number} участник`, `${number} участника`, `${number} участников`]
        return titles[number % 10 == 1 && number % 100 != 11 ? 0 : number % 10 >= 2 && number % 10 <= 4 && (number % 100 < 10 || number % 100 >= 20) ? 1 : 2]
      },

      createUniqueId(field) {
        let id = this.inputs[field].length + 1
        let isIdUnique = false

        while (!isIdUnique) {
          const existingItemWithId = this.inputs[field].find(duration => duration.id == id)
          if (!existingItemWithId) isIdUnique = true
          else id++
        }

        return id
      },

      validateMinClients({ target: { value }}) {
        value = Number(value)
        if (value < 1) return this.inputs.min_clients = 1
        if (value > this.inputs.max_clients) return this.inputs.min_clients = this.inputs.max_clients
      },
      validateMaxClients({ target: { value }}) {
        value = Number(value)
        if (value < this.inputs.min_clients) return this.inputs.max_clients = this.inputs.min_clients
      },
      validateAssetCount({ target: { value }}) {
        value = Number(value)
        if (value < 1) return this.inputs.asset_count = 1
      },

      validateDurationName(currentDuration) {
        const inputErrors = this.inputErrors.durationErrors[currentDuration.id] = []

        if (!currentDuration.name) {
          return inputErrors.push('Необходимо ввести название продолжительности')
        }

        for (let duration of this.inputs.durations) {
          if (currentDuration.id !== duration.id && currentDuration.name === duration.name) {
            inputErrors.push('Продолжительность с таким названием уже существует')
          }
        }
      },

      validateDuration(currentDuration) {

        if (currentDuration.duration < 1) {
          currentDuration.duration = 1
        }

        if (currentDuration.duration > 60 * 24) {
          currentDuration.duration = 60 * 24
        }
      },

      validatePrice(index, workzoneId, optionId) {
        index = Number(index)
        let min_clients = Number(this.inputs.min_clients)
        let option

        if (this.inputs.client_type === 'various') {
          option = this.table[this.selectedDuration][index + min_clients][`workzone_${workzoneId}`][optionId]
        } else {
          option = this.table[this.selectedDuration][this.inputs.client_type][`workzone_${workzoneId}`][optionId]
        }
        console.log('Валидируем прайс', option)

        if (option.price < 0) {
          console.log('что')
          option.price = 0
          console.log('option', option)
        }
      },

      validateOption(currentOption) {
        const inputErrors = this.inputErrors.optionErrors[currentOption.id] = []

        if (!currentOption.name) {
          return inputErrors.push('Необходимо ввести название опции')
        }

        for (let option of this.inputs.options) {
          if (currentOption.id !== option.id && currentOption.name === option.name) {
            inputErrors.push('Опция с таким названием уже существует')
          }
        }
      },

      validateWorkzone() {

      },

      // Форматирование массива рабочих зон
      // Нужно удалить русские имена дней недели
      createWorkzonesArray() {
        const workzones = []

        this.inputs.workzones.forEach(workzone => {
          let formattedWorkzone = {
            id: workzone.id || this.createUniqueId('workzones'),
            weekdays: [...workzone.weekdays.map(day => day.techname || day)],
            time: [...workzone.time]
          }
          workzones.push(formattedWorkzone)
        })

        return workzones
      },

      createCostsArray() {
        const costs = []


        for (let duration in this.table) {

          if (this.inputs.client_type === 'various') {

            for (let client in this.table[duration]) {
              const clientCount = client.split('_')[1]

              for (let workzone in this.table[duration][client]) {
                const workzoneId = workzone.split('_')[1]

                for (let option in this.table[duration][client][workzone]) {
                  let cost = this.table[duration][client][workzone][option].price

                  if (cost) {
                    costs.push({
                      id: costs.length,
                      service_id: this.service_id,
                      duration_id: duration,
                      option_id: option,
                      workzone_id: workzoneId,
                      min_clients: clientCount,
                      max_clients: clientCount,
                      cost
                    })
                  }
                }
              }
            }
          } else {
            for (let workzone in this.table[duration][this.inputs.client_type]) {
              const workzoneId = workzone.split('_')[1]

              for (let option in this.table[duration][this.inputs.client_type][workzone]) {
                let cost = this.table[duration][this.inputs.client_type][workzone][option].price

                if (cost) {
                  costs.push({
                    id: costs.length,
                    service_id: this.service_id,
                    duration_id: duration,
                    option_id: option,
                    workzone_id: workzoneId,
                    min_clients: this.inputs.min_clients,
                    max_clients: this.inputs.max_clients,
                    cost
                  })
                }
              }
            }
          }
        }

        return costs
      }
    }
  }
</script>

<style lang="sass" scoped>
  $grey: #eee
  $red: #d32f2f

  .price-component-wrapper
    display: flex
    width: 100%
    min-height: 100vh
    align-items: center
    justify-content: center
    font-size: 14px



  .price-component
    width: 100%
    padding: 2em 3em
    border: 1px solid $grey
    border-radius: 5px

  .inputs
    display: flex
    flex-direction: column

  .inputs-row
    display: flex
    flex-wrap: wrap
    margin: 10px 0
    align-items: center

  .row-title
    font-weight: bold
    font-size: 15px

  .error-text
    font-size: 12px
    margin-bottom: 10px
    letter-spacing: 0.06em

  .input-item
    display: flex
    flex-direction: column
    margin: 10px 0
    margin-right: 20px
    padding: 5px 20px
    border-radius: 5px
    border: 1px solid $grey

    &:hover .remove-button
      opacity: 1

    &-header
      display: flex
      align-items: center

      .remove-button
        opacity: 0
        cursor: pointer
        margin: 0 15px

    &-fields
      display: flex

    button
      height: 35px
      margin-right: 15px
      margin-bottom: 10px
      padding: 0 20px
      border-radius: 5px
      border: none
    // background-color: $accent

    label
      margin-top: 10px
      color: #212121
      font-weight: bold
      margin-bottom: 10px

    input, select
      cursor: pointer
      height: 40px
      padding: 0 15px
      border-radius: 5px
      border: 1px solid $grey
      margin-right: 15px
      margin-bottom: 10px
      &:last-of-type
        margin-right: 0

      &:focus, &:active
        outline: none

    &-invalid
      color: $red
      border: 1px solid $red

  input::-webkit-outer-spin-button,
  input::-webkit-inner-spin-button
    -webkit-appearance: none
    margin: 0

  input[type=number]
    -moz-appearance:textfield

  .payment-type-selector
    width: 270px
    margin-bottom: 10px
    font-size: 13px

  .add-button
    user-select: none
    display: flex
    cursor: pointer
    align-items: center
    justify-content: center
    width: 40px
    height: 40px
    // margin: 0 20px
    background-color: $grey
    border-radius: 50%

    &:hover
      background-color: #FFEB3B


  .tabs
    display: flex
    margin: 10px 0 20px
    width: 100%
    border-bottom: 1px solid $grey

  .tab
    user-select: none
    cursor: pointer
    display: flex
    align-items: center
    border: 1px solid $grey
    padding: 15px 25px
    margin-bottom: -1px
    &:not(:first-of-type)
      margin-left: -1px

  .selected-tab
    font-weight: bold
    background-color: $grey


  .table
    display: grid
    grid-template-columns: 220px 1fr

  .table-content-right
    padding: 0 20px
    display: flex
    overflow-x: auto

  // "Пустой" элемент для ячеек таблицы
  .empty
    height: 50px
    margin-bottom: 8px

  .table-content-item
    height: 50px
    margin-bottom: 8px
    width: 100%

  .members-count
    display: flex
    align-items: center
    justify-content: center
    height: 50px
    background-color: $grey
    margin-bottom: 8px

  .workzone
    display: flex
    flex-direction: column
    align-items: center
    justify-content: center
    font-size: 13px
    height: 50px
    margin-bottom: 8px

  .option-column
    margin-right: 20px
    flex: none
    max-width: 180px

  .option-name
    height: 50px
    padding: 0 25px
    border: 1px solid $grey
    font-size: 13px
    margin-bottom: 8px
    display: flex
    align-items: center
    justify-content: center

  .workzone-price input
    width: 100%
    border-radius: 5px
    border: 1px solid $grey
    height: 50px
    padding: 0 15px
    box-sizing: border-box
    margin-bottom: 8px

  .days-input
    margin-bottom: 10px

  .workzone-days
    display: flex

  .workzone-day
    margin-right: 4px

    &:after
      content: ','

    &:last-of-type:after
      content: ''

  .new-time-button
    margin-left: 10px
    margin-right: 0 !important

  .table-errors
    display: flex
    width: 100%
    padding: 40px 0
    flex-direction: column
    align-items: center
</style>