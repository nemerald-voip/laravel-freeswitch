<template>
    <form @submit.prevent="submitForm">
        <div>
            <div class="border-b border-gray-200 mb-4">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <a href="#"
                       @click.prevent="selectedTab = 0"
                       :class="[selectedTab === 0 ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700', 'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium']"
                       :aria-current="selectedTab === 0 ? 'page' : undefined">
                        Basic
                    </a>
                    <a href="#"
                       @click.prevent="selectedTab = 1"
                       :class="[selectedTab === 1 ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700', 'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium']"
                       :aria-current="selectedTab === 1 ? 'page' : undefined">
                        Advanced
                    </a>
                </nav>
            </div>

            <div v-if="selectedTab === 0">
                <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                    <div class="sm:col-span-12">
                        <LabelInputRequired :target="'destination_prefix'" :label="'Country Code'"/>
                        <div class="mt-2">
                            <InputField
                                v-model="form.destination_prefix"
                                type="text"
                                id="destination_prefix"
                                name="destination_prefix"
                                placeholder="Enter country code"
                                :error="errors?.destination_prefix && errors.destination_prefix.length > 0"/>
                        </div>
                    </div>
                    <div class="sm:col-span-12">
                        <LabelInputRequired :target="'destination_number'" :label="'Phone Number'"/>
                        <div class="mt-2">
                            <InputField
                                v-model="form.destination_number"
                                type="text"
                                id="destination_number"
                                name="destination_number"
                                placeholder="Enter phone number"
                                :error="errors?.destination_prefix && errors.destination_prefix.length > 0"/>
                        </div>
                    </div>
                    <div class="sm:col-span-12">
                        <LabelInputOptional :target="'destination_actions'" :label="'If not answered, calls will be sent'"/>
                        <TimeoutDestinations
                            :categories="options.categories"
                            :targets="options.targets"
                            :selectedItem="form.destination_actions"
                            :itemOptions="phoneNumber"
                        />
                    </div>
                    <div class="sm:col-span-12">
                        <LabelInputOptional :target="'destination_hold_music'" :label="'Music on Hold'"/>
                        <div class="mt-2">
                            <SelectBoxGroup :options="options.music_on_hold"
                                            :search="true"
                                            :allowEmpty="true"
                                            :selectedItem="null"
                                            :placeholder="'Choose music on hold'"
                                            @update:modal-value="handleUpdateMusicOnHold"
                            />
                        </div>
                    </div>
                    <div class="sm:col-span-12">
                        <LabelInputOptional :target="'destination_description'" :label="'Description'"/>
                        <div class="mt-2">
                            <Textarea v-model="form.destination_description" name="destination_description" rows="2" />
                        </div>
                    </div>
                    <div class="sm:col-span-12">
                        <Toggle
                            :target="'destination_enabled'"
                            :label="'Enable'"
                            :enabled="destinationEnabledTrigger"
                            @update:status="handleDestinationEnabled"
                        />
                    </div>
                </div>
            </div>

            <div v-if="selectedTab === 1">
                <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                    <div class="sm:col-span-12">
                        <Toggle
                            :target="'destination_record'"
                            :label="'Check to save recordings'"
                            :enabled="destinationRecordTrigger"
                            @update:status="handleDestinationRecord"
                        />
                    </div>
                    <div class="sm:col-span-12">
                        <LabelInputOptional :target="'fax_uuid'" :label="'Fax detection'"/>
                        <div class="mt-2">
                            <SelectBox :options="options.faxes"
                                       :search="true"
                                       :allowEmpty="true"
                                       :placeholder="'Choose fax'"
                                       @update:modal-value="handleUpdateFax"
                            />
                        </div>
                    </div>
                    <div class="sm:col-span-12">
                        <LabelInputOptional :target="'destination_cid_name_prefix'" :label="'Caller ID name prefix'" />
                        <div class="mt-2">
                            <InputField
                                v-model="form.destination_cid_name_prefix"
                                type="text"
                                id="destination_cid_name_prefix"
                                name="destination_cid_name_prefix"
                                placeholder="Enter caller prefix"
                                :error="errors?.destination_cid_name_prefix && errors.destination_cid_name_prefix.length > 0"/>
                        </div>
                    </div>
                    <div class="sm:col-span-12">
                        <LabelInputOptional :target="'destination_accountcode'" :label="'Account code'" />
                        <div class="mt-2">
                            <InputField
                                v-model="form.destination_accountcode"
                                type="text"
                                id="destination_accountcode"
                                name="destination_accountcode"
                                placeholder="Enter account code"
                                :error="errors?.destination_accountcode && errors.destination_accountcode.length > 0"/>
                        </div>
                    </div>

                    <div class="sm:col-span-12">
                        <LabelInputOptional :target="'destination_distinctive_ring'" :label="'Distinctive ring'" />
                        <div class="mt-2">
                            <InputField
                                v-model="form.destination_distinctive_ring"
                                type="text"
                                id="destination_distinctive_ring"
                                name="destination_distinctive_ring"
                                placeholder="Enter distinctive ring"
                                :error="errors?.destination_distinctive_ring && errors.destination_distinctive_ring.length > 0"/>
                        </div>
                    </div>

                    <div class="sm:col-span-12">
                        <LabelInputRequired :target="'domain_uuid'" :label="'Domain'"/>
                        <div class="mt-2">
                            <SelectBox :options="options.domains"
                                       :selectedItem="null"
                                       :search="true"
                                       :placeholder="'Choose domain'"
                                       @update:modal-value="handleUpdateDomain"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="border-t mt-4 sm:mt-4 ">
            <div class="mt-4 sm:mt-4 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                <button type="submit"
                        class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:col-start-2"
                        ref="saveButtonRef" :disabled="isSubmitting">
                    <Spinner :show="isSubmitting" />
                    Save
                </button>
                <button type="button"
                        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0"
                        @click="emits('cancel')" ref="cancelButtonRef">Cancel
                </button>
            </div>
        </div>
    </form>
</template>

<script setup>
import {defineProps, onMounted, reactive, ref} from 'vue'
import LabelInputRequired from "../general/LabelInputRequired.vue";
import LabelInputOptional from "../general/LabelInputOptional.vue";
import Toggle from "../general/Toggle.vue";
import SelectBoxGroup from "../general/SelectBoxGroup.vue";
import TimeoutDestinations from "../general/TimeoutDestinations.vue";
import InputField from "../general/InputField.vue";
import Textarea from "../general/Textarea.vue";
import {usePage} from "@inertiajs/vue3";
import Spinner from "../general/Spinner.vue";
import SelectBox from "../general/SelectBox.vue";

const destinationEnabledTrigger = ref(false);

const props = defineProps({
    options: Object,
    isSubmitting: Boolean,
    errors: Object,
});

const emits = defineEmits(['submit', 'cancel']);

const page = usePage();

const selectedTab = ref(0)

const form = reactive({
    destination_prefix: null,
    destination_number: null,
    destination_actions: null,
    destination_description: null,
    _token: page.props.csrf_token,
})

const submitForm = () => {
    emits('submit', form); // Emit the event with the form data
}

/*onMounted(() => {
    if(!Array.isArray(props.phoneNumber.destination_actions)) {
        props.phoneNumber.destination_actions = [];
    }
    destinationEnabledTrigger.value = props.phoneNumber.destination_enabled;
});*/

const handleUpdateMusicOnHold = (newSelectedItem) => {
    if (newSelectedItem !== null && newSelectedItem !== undefined) {
        form.destination_hold_music = newSelectedItem.value;
    } else {
        form.destination_hold_music = '';
    }
}

const handleDestinationEnabled = (newSelectedItem) => {
    form.destination_enabled = newSelectedItem;
    destinationEnabledTrigger.value = newSelectedItem
}

</script>