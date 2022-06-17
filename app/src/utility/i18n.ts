import {get} from 'lodash-es';

declare var collectme: any;

export default function (path: string): string {
    if (!collectme?.t) {
        return path
    }

    return get(collectme.t, path, path)
}