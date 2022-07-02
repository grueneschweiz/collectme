import {get} from 'lodash-es';

export default function (path: string): string {
    if (!collectme.t) {
        return path
    }

    return get(collectme.t, path, path)
}