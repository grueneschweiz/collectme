export type ObjectiveSettings = {
  id: string;
  name: string;
  enabled: boolean;
  objective: number;
  img: string;
  hot: string;
};

export function useObjectiveSettings() {
  function getGreatest(): ObjectiveSettings {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    return (<any>Object)
      .values(collectme.objectives)
      .filter((objective: ObjectiveSettings) => objective.enabled)
      .reduce((carry: ObjectiveSettings, current: ObjectiveSettings) =>
        carry.objective > current.objective ? carry : current
      );
  }

  function getSorted(): ObjectiveSettings[] {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    return (<any>Object)
      .values(collectme.objectives)
      .filter((objective: ObjectiveSettings) => objective.enabled)
      .sort(
        (a: ObjectiveSettings, b: ObjectiveSettings) =>
          a.objective - b.objective
      );
  }

  function getDefault(): ObjectiveSettings {
    return collectme.defaultObjective as ObjectiveSettings;
  }

  function isHot(objective: number): boolean {
    return (
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      (<any>Object)
        .values(collectme.objectives)
        .filter(
          (o: ObjectiveSettings) =>
            o.enabled && o.hot && o.objective === objective
        ).length > 0
    );
  }

  function getLowerOrEqual(count: number): ObjectiveSettings {
    let last = getDefault();
    for (const objective of getSorted()) {
      if (objective.objective > count) {
        break;
      } else {
        last = objective;
      }
    }

    return last;
  }

  return {
    getDefault,
    getLowerOrEqual,
    getGreatest,
    getSorted,
    isHot,
  };
}
