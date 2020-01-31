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
                                input(v-model="inputs.min_clients" type="number" placeholder="Минимум" @input="validateMinClients")
                                input(v-model="inputs.max_clients" type="number" placeholder="Максимум" @input="validateMaxClients")

                        .input-item
                            label Число активностей
                            input(v-model="inputs.asset_count")
                            //- .error-text Ошибка!

                    .inputs-row
                        .input-item
                            label Тип оплаты
                            multiselect.payment-type-selector(
                                v-model="inputs.payment_type"
                                :options="paymentTypes"
                                label="name"
                            )

                        .input-item(v-if="inputs.payment_type && inputs.payment_type.techname === 'team'")
                            label Тип цены
                            multiselect.payment-type-selector(
                                v-model="inputs.price_type"
                                :options="priceTypes"
                                label="name"
                            )

                        .input-item(v-if="inputs.payment_type && inputs.payment_type.techname === 'team' && inputs.price_type.techname === 'fix_client'")
                            label Фиксированная цена
                            input(v-model="inputs.fix_price")

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
                                input(v-model="duration.name" placeholder="Название" @input="validateDuration(duration)")
                                input(v-model="duration.duration" placeholder="Длительность (мин.)")
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
                            multiselect.payment-type-selector(
                                v-model="inputs.workzones_type"
                                :options="workzonesTypes"
                                label="name"
                            )
                    .inputs-row
                        .input-item(v-for="(workzone, index) in inputs.workzones" :key="index")
                            .input-item-header
                                label Рабочая зона {{ index + 1 }}
                                .remove-button(v-if="inputs.workzones.length > 1" @click="removeWorkzone(workzone)") x
                            .inputs-item-fields
                                .workzone-days
                                    .workzone-day(v-for="day in workzone.weekdays") {{ day.name || days.find(item => item.techname === day).name }}
                                .workzone-hours(style="margin-bottom: 10px")
                                    span(v-for="(time, i) in workzone.time")
                                        span(v-if="inputs.workzones_type.techname === 'auto'") {{ time.from }} - {{ time.to }}
                                        span(v-else-if="inputs.workzones_type.techname === 'manual'") {{ time.from }}
                                        span(v-if="workzone.time.length > 1 && i + 1 != workzone.time.length") ,

                        .input-item(v-if="showNewWorkzoneForm")
                            label Добавить рабочую зону
                            .inputs-item-fields
                                .new-workzone-hours(v-for="(time, index) in newWorkzone.time" :key="time.id")
                                    input(v-model="newWorkzone.time[index].from" placeholder="От" v-mask="'##:##'")
                                    input(v-if="inputs.workzones_type.techname === 'auto'" v-model="newWorkzone.time[index].to" placeholder="До" v-mask="'##:##'")
                                    button.new-time-button(v-if="index === 0" @click="addTimeInNewWorkzone()") +

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

                                .error-text Ошибочки

                                button(@click="addWorkzone") Добавить
                                button(@click="showNewWorkzoneForm = false") Отмена
                        .add-button(@click="showNewWorkzoneForm = true" v-if="!showNewWorkzoneForm") +


                .row-title Таблица цен
                .tabs
                    .tab(v-for="(tab, index) in inputs.durations" :key="index" v-if="tab.name" @click="selectedDuration = tab.id" :class="{ 'selected-tab': tab.id == selectedDuration }") {{ tab.name }}
                        span(v-if="tab.duration") , {{ tab.duration }}

                .table(v-if="isTableReady")
                    .table-content-left
                        .empty
                        .member-item(v-if="inputs.payment_type.techname === 'flexible'" v-for="(member, index) in (inputs.max_clients - inputs.min_clients + 1)")
                            .members-count {{ pluralizeMemberWord(Number(index) + Number(inputs.min_clients)) }}
                            .workzones
                                .workzone(v-for="(workzone, index) in inputs.workzones" :key="index")
                                    .workzone-days
                                        .workzone-day(v-for="day in workzone.weekdays") {{ day.name || days.find(item => item.techname === day).name }}
                                    .workzone-hours(v-for="(time, i) in workzone.time")
                                        span(v-if="inputs.workzones_type.techname === 'auto'") {{ time.from }} - {{ time.to }}
                                        span(v-else-if="inputs.workzones_type.techname === 'manual'") {{ time.from }}
                                        span(v-if="workzone.time.length > 1 && i + 1 != workzone.time.length") ,

                        .member-item(v-if="inputs.payment_type.techname !== 'flexible'")
                            .members-count(v-if="inputs.payment_type.techname === 'team'") Оплата за группу ({{ `${inputs.min_clients}-${inputs.max_clients}` }})
                            .members-count(v-else-if="inputs.payment_type.techname === 'client'") 1 участник
                            .workzones
                                .workzone(v-for="(workzone, index) in inputs.workzones" :key="index")
                                    .workzone-days
                                        .workzone-day(v-for="day in workzone.weekdays") {{ day.name || days.find(item => item.techname === day).name }}
                                    .workzone-hours(v-for="(time, i) in workzone.time")
                                        span(v-if="inputs.workzones_type.techname === 'auto'") {{ time.from }} - {{ time.to }}
                                        span(v-else-if="inputs.workzones_type.techname === 'manual'") {{ time.from }}
                                        span(v-if="workzone.time.length > 1 && i + 1 != workzone.time.length") ,


                    .table-content-right
                        .option-column(v-for="(option, index) in inputs.options" :key="index" v-if="option.name")
                            .option-name {{ option.name }}
                            //- Инпуты для типа оплаты "за клиента"
                            template(v-if="inputs.payment_type.techname === 'flexible'" v-for="(member, index) in (inputs.max_clients - inputs.min_clients + 1)")
                                .empty
                                .workzone-price
                                    input(
                                        v-for="workzone in inputs.workzones"
                                        placeholder="цена"
                                        v-model="table[selectedDuration][`client_${Number(index) + Number(inputs.min_clients)}`][`workzone_${workzone.id}`][option.id].price"
                                    )

                            //- Инпуты для остальных типов
                            template(v-if="inputs.payment_type.techname !== 'flexible'")
                                .empty
                                .workzone-price
                                    input(
                                        v-for="workzone in inputs.workzones"
                                        placeholder="цена"
                                        v-model="table[selectedDuration][inputs.payment_type.techname][`workzone_${workzone.id}`][option.id].price"
                                    )





</template>

<script>
  import {FormField, HandlesValidationErrors} from 'laravel-nova';
  import Multiselect from 'vue-multiselect';

  let TEST_DATA = {
    'service_id': 2,
    'min_clients': 1,
    'max_clients': 3,
    'asset_count': 4,
    'payment_type': 'client',
    // price_type просто для примера
    'price_type': 'client',
    'workzones_type': 'auto',
    'durations': [
      {
        'id': 2249,
        'name': 'прокат с инструктором - 60 мин',
        'duration': 60,
        'service_id': 2,
        'created_at': '2020-01-27 10:35:36',
        'updated_at': '2020-01-27 10:35:36',
      },
      {
        'id': 2250,
        'name': 'катание с pro гонщиком - 15 мин',
        'duration': 15,
        'service_id': 2,
        'created_at': '2020-01-27 10:35:36',
        'updated_at': '2020-01-27 10:35:36',
      },
      {
        'id': 2251,
        'name': 'катание с pro гонщиком - 60 мин',
        'duration': 60,
        'service_id': 2,
        'created_at': '2020-01-27 10:35:36',
        'updated_at': '2020-01-27 10:35:36',
      },
    ],
    'options': [
      {
        'id': 1201,
        'name': 'Стандарт',
        'service_id': 2,
        'created_at': '2020-01-27 10:35:36',
        'updated_at': '2020-01-27 10:35:36',
      },
    ],
    'workzones': [
      {
        'id': 1566,
        'weekdays': [
          'tuesday',
          'wednesday',
          'thursday',
          'friday',
          'saturday',
          'sunday',
        ],
        'time': [
          {
            'from': '10:00',
            'to': '20:00',
          },
        ],
        'service_id': 2,
        'created_at': '2020-01-27 10:35:36',
        'updated_at': '2020-01-27 10:35:36',
      },
    ],
    'costs': [
      {
        'id': 3366,
        'service_id': 2,
        'duration_id': 2249,
        'option_id': 1201,
        'workzone_id': 1566,
        'cost': 12000,
        'min_clients': 1,
        'max_clients': 1,
        'created_at': '2020-01-27 10:35:36',
        'updated_at': '2020-01-27 10:35:36',
      },
      {
        'id': 3367,
        'service_id': 2,
        'duration_id': 2250,
        'option_id': 1201,
        'workzone_id': 1566,
        'cost': 5000,
        'min_clients': 2,
        'max_clients': 2,
        'created_at': '2020-01-27 10:35:36',
        'updated_at': '2020-01-27 10:35:36',
      },
      {
        'id': 3368,
        'service_id': 2,
        'duration_id': 2251,
        'option_id': 1201,
        'workzone_id': 1566,
        'cost': 20000,
        'min_clients': 3,
        'max_clients': 3,
        'created_at': '2020-01-27 10:35:36',
        'updated_at': '2020-01-27 10:35:36',
      },
    ],
  };

  export default {
    components: {Multiselect},

    mixins: [FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    data() {
      return {
        service_id: null,

        days: [
          {
            name: 'Пн',
            techname: 'monday',
          },
          {
            name: 'Вт',
            techname: 'tuesday',
          },
          {
            name: 'Ср',
            techname: 'wednesday',
          },
          {
            name: 'Чт',
            techname: 'thursday',
          },
          {
            name: 'Пт',
            techname: 'friday',
          },
          {
            name: 'Сб',
            techname: 'saturday',
          },
          {
            name: 'Вс',
            techname: 'sunday',
          },
        ],
        paymentTypes: [
          {
            name: 'Индивидуальный',
            techname: 'client',
          },
          {
            name: 'Группа участников',
            techname: 'team',
          },
          {
            name: 'Зависит от числа участников',
            techname: 'flexible',
          },
        ],
        priceTypes: [
          {
            name: 'За участника',
            techname: 'client',
          },
          {
            name: 'За команду',
            techname: 'team',
          },
          {
            name: 'Фикс + за участника',
            techname: 'fix_client',
          },
        ],
        workzonesTypes: [
          {
            name: 'Автоматический',
            techname: 'auto',
          },
          {
            name: 'Ручной',
            techname: 'manual',
          },
        ],
        showNewWorkzoneForm: false,
        newWorkzone: {
          weekdays: [],
          time: [
            {
              id: 1,
              from: '',
              to: '',
            },
          ],
        },
        selectedDuration: 1,

        // Поля ввода
        inputs: {
          min_clients: 1,
          max_clients: 2,
          asset_count: 1,
          fix_price: 0,
          isPaymentTypeChanged: false,
          payment_type: null,
          workzones_type: null,
          price_type: null,
          durations: [],
          options: [],
          workzones: [],
        },

        inputErrors: {
          optionErrors: {},
          durationErrors: {},
          newWorkzoneErrors: [],
        },

        isTableReady: false,
        table: {},
      };
    },
    mounted() {
      // this.setInitialValues(TEST_DATA);
      // this.setFieldsForTable(TEST_DATA);
    },
    watch: {
      'inputs': {
        handler() {
          this.isTableReady = false;
          this.setFieldsForTable();
        },
        deep: true,
      },
      'inputs.payment_type'(previousValue, currentValue) {
        if (previousValue && currentValue) {
          if (previousValue.techname !== currentValue.techname) {
            return this.inputs.isPaymentTypeChanged = true;
          }
        }
      },
    },

    methods: {
      setInitialValue() {
        this.setInitialValues(this.field);
        this.setFieldsForTable(this.field);
      },

      fill(formData) {
        let data = {
          service_id: this.service_id,
          min_clients: this.inputs.min_clients,
          max_clients: this.inputs.max_clients,
          asset_count: this.inputs.asset_count,
          payment_type: this.inputs.payment_type.techname,
          price_type: this.inputs.price_type,
          fix_price: this.inputs.fix_price,
          workzones_type: this.inputs.workzones_type,
          durations: this.inputs.durations,
          options: this.inputs.options,
          workzones: this.inputs.workzones,
          costs: this.createCostsArray(),
        };

        formData.append(this.field.attribute, JSON.stringify(data) || []);
      },

      logTable() {
        console.log('This table', this.table);
      },

      setInitialValues(data) {
        // TODO: сменить test_data на prop с нужным названием

        this.service_id = data.service_id || null;
        this.inputs.min_clients = data.min_clients || 1;
        this.inputs.max_clients = data.max_clients || 1;
        this.inputs.fix_price = data.fix_price || 0;
        this.inputs.asset_count = data.asset_count || 1;
        this.inputs.prices = data.costs || [];

        this.inputs.durations = data.durations || [
          {
            id: 1,
            name: 'Продолжительность 1',
            duration: '90',
          }];

        this.selectedDuration = this.inputs.durations[0].id;

        this.inputs.options = data.options || [
          {
            id: 1,
            name: 'Опция 1',
          }];

        this.inputs.workzones = data.workzones || [
          {
            id: 1,
            time: [
              {
                from: '08:00',
                to: '20:00',
              }],
            weekdays: [
              {
                name: 'Пн',
                techname: 'monday',
              },
              {
                name: 'Вт',
                techname: 'tuesday',
              },
              {
                name: 'Ср',
                techname: 'wednesday',
              },
              {
                name: 'Чт',
                techname: 'thursday',
              },
              {
                name: 'Пт',
                techname: 'friday',
              },
            ],
          }];

        const paymentType = this.paymentTypes.find(type => type.techname === data.payment_type);

        this.inputs.payment_type = paymentType || {
          name: 'Индивидуальный',
          techname: 'client',
        };

        const workzonesType = this.workzonesTypes.find(type => type.techname === data.workzones_type);

        this.inputs.workzones_type = workzonesType || {
          name: 'Автоматический',
          techname: 'auto',
        };

        const priceType = this.priceTypes.find(type => type.techname === data.price_type);

        this.inputs.price_type = priceType || {
          name: 'За участника',
          techname: 'client',
        };
      },

      // Нужно подготовить реактивные свойства для полей цен
      setFieldsForTable(data) {
        this.isTableReady = false;

        // console.log('Строим таблицу для', this.inputs.payment_type.techname)

        // Строим таблицу циклами
        // Проходим по всем продолжительностям (durations)
        for (let d = 0; d < this.inputs.durations.length; d++) {
          let duration = this.table[this.inputs.durations[d].id];

          // // Для каждой продолжительности создаем объект, ключ которого - id продолжительности
          if (!duration || this.inputs.isPaymentTypeChanged) {
            duration = this.table[this.inputs.durations[d].id] = {};
          }

          // Блок кода для типа оплаты "зависит от числа участников"
          // Строятся поля для каждого клиента
          if (this.inputs.payment_type.techname === 'flexible') {
            // Этот объект будет содержать поле для каждого участника
            for (let m = this.inputs.min_clients; m <= this.inputs.max_clients; m++) {
              let client = duration[`client_${m}`];

              // создаем пустой объект для каждого участника
              if (!client) {
                client = duration[`client_${m}`] = {};
              }

              // создаем объект для каждой воркзоны
              for (let w = 0; w < this.inputs.workzones.length; w++) {
                let workzone = client[`workzone_${this.inputs.workzones[w].id}`];

                if (!workzone) {
                  workzone = client[`workzone_${this.inputs.workzones[w].id}`] = {};
                }

                // ...и объект для каждой опции
                for (let o = 0; o < this.inputs.options.length; o++) {
                  let option = workzone[this.inputs.options[o].id];

                  let price = 0;

                  if (data && data.costs && data.costs.length) {
                    const priceObj = data.costs.find(cost => (
                            cost.duration_id == this.inputs.durations[d].id &&
                            cost.min_clients == m &&
                            cost.workzone_id == this.inputs.workzones[w].id &&
                            cost.option_id == this.inputs.options[o].id
                        ),
                    );
                    if (priceObj) {
                      price = priceObj.cost;
                    }
                  }

                  if (!option) {
                    workzone[this.inputs.options[o].id] = {price};
                  }
                }
              }
            }

            // Удаляем лишних клиентов
            for (let client in duration) {
              let clientNumber = client.split('_')[1];

              if (clientNumber > this.inputs.max_clients || clientNumber < this.inputs.min_clients) {
                delete duration[client];
              }
            }
          }

          // Если тип оплаты "индивидуальный"
          if (this.inputs.payment_type.techname === 'client') {
            let client = duration.client;

            if (!client) {
              client = duration.client = {};
            }

            // создаем объект для каждой воркзоны
            for (let w = 0; w < this.inputs.workzones.length; w++) {
              let workzone = client[`workzone_${this.inputs.workzones[w].id}`];

              if (!workzone) {
                workzone = client[`workzone_${this.inputs.workzones[w].id}`] = {};
              }

              // ...и объект для каждой опции
              for (let o = 0; o < this.inputs.options.length; o++) {
                const option = workzone[this.inputs.options[o].id];

                let price = 0;

                if (data && data.costs && data.costs.length) {
                  const priceObj = data.costs.find(cost => (
                          cost.duration_id == this.inputs.durations[d].id &&
                          cost.workzone_id == this.inputs.workzones[w].id &&
                          cost.option_id == this.inputs.options[o].id
                      ),
                  );
                  if (priceObj) {
                    price = priceObj.cost;
                  }
                }

                if (!option) {
                  workzone[this.inputs.options[o].id] = {price};
                }
              }
            }
          }

          // Если тип оплаты команда
          if (this.inputs.payment_type.techname === 'team') {
            let team = duration.team;

            if (!team) {
              team = duration.team = {};
            }

            // создаем объект для каждой воркзоны
            for (let w = 0; w < this.inputs.workzones.length; w++) {
              let workzone = team[`workzone_${this.inputs.workzones[w].id}`];

              if (!workzone) {
                workzone = team[`workzone_${this.inputs.workzones[w].id}`] = {};
              }

              // ...и объект для каждой опции
              for (let o = 0; o < this.inputs.options.length; o++) {
                let option = workzone[this.inputs.options[o].id];
                let price = 0;

                if (data && data.costs && data.costs.length) {
                  const priceObj = data.costs.find(cost => (
                          cost.duration_id == this.inputs.durations[d].id &&
                          cost.workzone_id == this.inputs.workzones[w].id &&
                          cost.option_id == this.inputs.options[o].id
                      ),
                  );
                  if (priceObj) {
                    price = priceObj.cost;
                  }
                }

                if (!option) {
                  workzone[this.inputs.options[o].id] = {price};
                }
              }
            }
          }
        }

        // console.log('Таблица готова!', this.table)

        this.isTableReady = true;
        this.inputs.isPaymentTypeChanged = false;
      },

      addDuration() {
        let id = this.createUniqueId('durations');
        this.inputs.durations.push({id, name: '', duration: null});
      },
      removeDuration(item) {
        const index = this.inputs.durations.indexOf(item);
        this.inputs.durations.splice(index, 1);

        if (this.selectedDuration === item.id) {
          this.selectedDuration = this.inputs.durations[0].id;
        }

        delete this.table[item.id];
      },

      addOption() {
        let id = this.createUniqueId('options');
        this.inputs.options.push({id, name: ''});
      },

      removeOption(item) {
        const index = this.inputs.options.indexOf(item);
        this.inputs.options.splice(index, 1);

        for (let duration in this.table) {
          for (let client in this.table[duration]) {
            for (let workzone in this.table[duration][client]) {
              // удалеям поле цены для удаляемой опции для каждой воркзоны. Oh, shi!
              delete this.table[duration][client][workzone][item.id];
            }
          }
        }
      },

      addWorkzone() {
        let id = this.createUniqueId('workzones');

        this.inputs.workzones.push({
          id,
          weekdays: this.newWorkzone.weekdays,
          time: this.newWorkzone.time,
        });

        let newTimeObj = {};

        if (this.inputs.workzones_type === 'auto') {
          newTimeObj = {
            from: '',
            to: '',
          };
        }
        else if (this.inputs.workzones_type === 'manual') {
          newTimeObj = {
            from: '',
          };
        }

        this.newWorkzone.weekdays = [];
        this.newWorkzone.time = [newTimeObj];
      },

      addTimeInNewWorkzone() {
        let newTime = {};

        if (this.inputs.workzones_type === 'auto') {
          newTime = {
            from: '',
            to: '',
          };
        }
        else if (this.inputs.workzones_type === 'manual') {
          newTime = {
            from: '',
          };
        }

        this.newWorkzone.time.push(newTime);
      },

      removeWorkzone(item) {
        const index = this.inputs.workzones.indexOf(item);
        this.inputs.workzones.splice(index, 1);

        for (let duration in this.table) {
          for (let client in this.table[duration]) {
            delete this.table[duration][client][`workzone_${item.id}`];
          }
        }

      },

      pluralizeMemberWord(number) {
        const titles = [`${number} участник`, `${number} участника`, `${number} участников`];
        return titles[number % 10 == 1 && number % 100 != 11 ? 0 : number % 10 >= 2 && number % 10 <= 4 &&
        (number % 100 < 10 || number % 100 >= 20) ? 1 : 2];
      },

      createUniqueId(field) {
        let id = this.inputs[field].length + 1;
        let isIdUnique = false;

        while (!isIdUnique) {
          const existingItemWithId = this.inputs[field].find(duration => duration.id == id);
          if (!existingItemWithId) {
            isIdUnique = true;
          }
          else {
            id++;
          }
        }

        return id;
      },

      validateMinClients({target: {value}}) {
        value = Number(value);
        if (value < 1) {
          return this.inputs.min_clients = 1;
        }
        if (value > this.inputs.max_clients) {
          return this.inputs.min_clients = this.inputs.max_clients;
        }
      },
      validateMaxClients({target: {value}}) {
        value = Number(value);
        if (value < this.inputs.min_clients) {
          return this.inputs.max_clients = this.inputs.min_clients;
        }
      },

      validateDuration(currentDuration) {
        const inputErrors = this.inputErrors.durationErrors[currentDuration.id] = [];

        if (!currentDuration.name) {
          return inputErrors.push('Необходимо ввести название продолжительности');
        }

        for (let duration of this.inputs.durations) {
          if (currentDuration.id !== duration.id && currentDuration.name === duration.name) {
            inputErrors.push('Продолжительность с таким названием уже существует');
          }
        }
      },

      validateOption(currentOption) {
        const inputErrors = this.inputErrors.optionErrors[currentOption.id] = [];

        if (!currentOption.name) {
          return inputErrors.push('Необходимо ввести название опции');
        }

        for (let option of this.inputs.options) {
          if (currentOption.id !== option.id && currentOption.name === option.name) {
            inputErrors.push('Опция с таким названием уже существует');
          }
        }
      },

      validateWorkzone() {

      },

      formatDataForBackend() {
        const data = {
          service_id: this.service_id,
          min_clients: this.inputs.min_clients,
          max_clients: this.inputs.max_clients,
          asset_count: this.inputs.asset_count,
          payment_type: this.inputs.payment_type.techname,
          price_type: this.inputs.price_type,
          fix_price: this.inputs.fix_price,
          workzones_type: this.inputs.workzones_type,
          durations: this.inputs.durations,
          options: this.inputs.options,
          workzones: this.inputs.workzones,
          costs: this.createCostsArray(),
        };

        // console.log('ДАННЫЕ ДЛЯ БЕЕКА', data)
      },

      createCostsArray() {
        const costs = [];

        for (let duration in this.table) {

          if (this.inputs.payment_type.techname === 'flexible') {

            for (let client in this.table[duration]) {
              const clientCount = client.split('_')[1];

              for (let workzone in this.table[duration][client]) {
                const workzoneId = workzone.split('_')[1];

                for (let option in this.table[duration][client][workzone]) {
                  let cost = this.table[duration][client][workzone][option].price;

                  if (cost) {
                    costs.push({
                      id: costs.length,
                      service_id: this.service_id,
                      duration_id: duration,
                      option_id: option,
                      workzone_id: workzoneId,
                      min_clients: clientCount,
                      max_clients: clientCount,
                      cost,
                    });
                  }
                }
              }
            }
          }
          else {
            for (let workzone in this.table[duration][this.inputs.payment_type.techname]) {
              const workzoneId = workzone.split('_')[1];

              for (let option in this.table[duration][this.inputs.payment_type.techname][workzone]) {
                let cost = this.table[duration][this.inputs.payment_type.techname][workzone][option].price;

                if (cost) {
                  costs.push({
                    id: costs.length,
                    service_id: this.service_id,
                    duration_id: duration,
                    option_id: option,
                    workzone_id: workzoneId,
                    min_clients: this.inputs.min_clients,
                    max_clients: this.inputs.max_clients,
                    cost,
                  });
                }
              }
            }
          }
        }

        return costs;
      },

    },
  };
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
    max-width: 1000px
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

    input
      height: 40px
      padding: 0 15px
      border-radius: 5px
      border: 1px solid $grey
      margin-right: 15px
      margin-bottom: 10px

      &:last-of-type
        margin-right: 0

    &-invalid
      color: $red
      border: 1px solid $red


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

</style>