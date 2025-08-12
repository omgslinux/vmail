import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
//import 'bootstrap';
import * as bootstrap from 'bootstrap'; // 👈 importa todos los módulos
window.bootstrap = bootstrap; // 👈 expón Bootstrap al global scope
