import { createApp } from 'vue';
import axios from 'axios';

const app = createApp({
    data() {
        console.log('Vue app data initialized.');
        return {
            email: '',
            firstName: '',
            lastName: '',
            dob: '',
            marketingConsent: false,
            listIds: '',
            subscriberId: '',
            message: '',
            responseMessage: '',
            errorMessage: '',
        };
    },
    methods: {
        async addSubscriber() {
            this.responseMessage = '';
            this.errorMessage = '';
            console.log('Attempting to add subscriber with data:', {
                email: this.email,
                firstName: this.firstName,
                lastName: this.lastName,
                dob: this.dob,
                marketingConsent: this.marketingConsent,
                lists: this.listIds,
            });
            try {
                const response = await axios.post('/api/add-subscriber', {
                    email: this.email,
                    firstName: this.firstName,
                    lastName: this.lastName,
                    dob: this.dob,
                    marketingConsent: this.marketingConsent,
                    lists: this.listIds,
                });
                this.responseMessage = response.data.message;
                console.log('Add subscriber successful:', response.data);
            } catch (error) {
                this.errorMessage = error.response?.data?.message || 'Error: An unexpected issue occurred.';
                console.error('Add subscriber failed:', error.response?.data || error.message);
            }
        },
        async sendEnquiry() {
            this.responseMessage = '';
            this.errorMessage = '';
            try {
                const response = await axios.post('/api/send-enquiry', {
                    subscriberId: this.subscriberId,
                    message: this.message,
                });
                this.responseMessage = response.data.message;
            } catch (error) {
                this.errorMessage = error.response?.data?.message || 'Error: An unexpected issue occurred.';
            }
        },
    },
}).mount('#app');
