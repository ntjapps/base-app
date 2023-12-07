import { activatePreTesting, returnPostTesting } from './swap-env';

before(() => {
    activatePreTesting();
});

after(() => {
    returnPostTesting();
});